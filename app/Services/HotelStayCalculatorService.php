<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Room;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class HotelStayCalculatorService
{
    /**
     * Calculate billable hotel nights based on the standard 12:00 PM (noon) hotel day.
     * Hotel day begins at 12:00 PM and ends at 12:00 PM next day.
     *
     * @param CarbonInterface|string $checkIn
     * @param CarbonInterface|string|null $checkOut
     * @return int
     */
    public static function calculateHotelNights($checkIn, $checkOut = null): int
    {
        $in = $checkIn instanceof CarbonInterface ? $checkIn->copy() : Carbon::parse($checkIn);
        $out = $checkOut ? ($checkOut instanceof CarbonInterface ? $checkOut->copy() : Carbon::parse($checkOut)) : Carbon::now();

        // If checkout is chronologically before check-in, normalize checkout to at least check-in
        if ($out->lt($in)) {
            $out = $in->copy();
        }

        // Start cycle: If arrival is before 12:00 PM noon, it uses the previous calendar day's 12:00 PM cycle (early check-in)
        $startCycle = $in->format('H:i:s') < '12:00:00'
            ? $in->copy()->subDay()->startOfDay()
            : $in->copy()->startOfDay();

        // End cycle: If departure is strictly after 12:00 PM noon, it enters the next hotel day cycle (late check-out)
        $endCycle = $out->format('H:i:s') > '12:00:00'
            ? $out->copy()->addDay()->startOfDay()
            : $out->copy()->startOfDay();

        $nights = (int) $startCycle->diffInDays($endCycle);

        return max(1, $nights);
    }

    /**
     * Get the nightly room rate for the booking based on room type, currency, and historical booking value.
     *
     * @param Booking $booking
     * @return float
     */
    public static function getNightlyRate(Booking $booking): float
    {
        $booking->loadMissing('room');
        $currency = $booking->currency ?: 'USD';

        if ($booking->room) {
            if ($currency === 'USD') {
                $usd = (float) ($booking->room->price_usd ?? 0);
                if ($usd > 0) {
                    return $usd;
                }
                $syp = (float) ($booking->room->price ?? 0);
                if ($syp > 0) {
                    return round($syp / 15000, 2);
                }
            } else {
                $syp = (float) ($booking->room->price ?? 0);
                if ($syp > 0) {
                    return $syp;
                }
            }
        }

        // Fallback: derive from existing booking total price and original nights
        $checkInDate = Carbon::parse($booking->check_in);
        $checkOutDate = Carbon::parse($booking->check_out);
        $originalNights = max(1, (int) $checkInDate->diffInDays($checkOutDate));

        return round(((float) $booking->total_price) / $originalNights, 2);
    }

    /**
     * Execute check-in: update actual check-in timestamp and date, and adjust projected check-out.
     *
     * @param Booking $booking
     * @param CarbonInterface|string|null $actualCheckInTime
     * @return Booking
     */
    public static function processCheckIn(Booking $booking, $actualCheckInTime = null): Booking
    {
        $now = $actualCheckInTime
            ? ($actualCheckInTime instanceof CarbonInterface ? $actualCheckInTime->copy() : Carbon::parse($actualCheckInTime))
            : Carbon::now();

        $booking->loadMissing('room');

        $oldCheckIn = $booking->check_in ? Carbon::parse($booking->check_in) : $now->copy();
        $oldCheckOut = $booking->check_out ? Carbon::parse($booking->check_out) : $now->copy()->addDay();
        $originalNights = max(1, (int) $oldCheckIn->diffInDays($oldCheckOut));

        if (Schema::hasColumn('bookings', 'actual_check_in_at')) {
            $booking->actual_check_in_at = $now;
        }
        $booking->check_in = $now->toDateString();

        // If current check_out is <= new check_in, project check_out by preserving original nights
        $currentCheckOut = Carbon::parse($booking->check_out);
        if ($currentCheckOut->lte(Carbon::parse($booking->check_in))) {
            $booking->check_out = $now->copy()->addDays($originalNights)->toDateString();
        }

        // Update booking total price for the interim period
        $projectedNights = max(1, (int) Carbon::parse($booking->check_in)->diffInDays(Carbon::parse($booking->check_out)));
        $nightlyRate = static::getNightlyRate($booking);
        $newTotal = round($projectedNights * $nightlyRate, 2);

        $booking->total_price = $newTotal;
        if ($booking->currency === 'USD') {
            $booking->total_price_usd = $newTotal;
        }

        $booking->status = 'checked_in';
        $booking->save();

        if ($booking->room) {
            $booking->room->update(['status' => 'occupied']);
        }

        return $booking;
    }

    /**
     * Execute check-out and final invoice calculation based on actual stay duration and hotel day cycle,
     * respecting any manual overrides specified by reception, accountant, or manager.
     *
     * @param Booking $booking
     * @param array $options
     * @return array{booking: Booking, invoice: Invoice, days: int, nightly_rate: float, room_amount: float}
     */
    public static function processCheckOut(Booking $booking, array $options = []): array
    {
        return DB::transaction(function () use ($booking, $options) {
            $lockedBooking = Booking::whereKey($booking->id)
                ->lockForUpdate()
                ->with(['room', 'guest', 'hotel'])
                ->firstOrFail();

            $now = !empty($options['check_out_time'])
                ? ($options['check_out_time'] instanceof CarbonInterface ? $options['check_out_time'] : Carbon::parse($options['check_out_time']))
                : Carbon::now();

            $hasActualCheckIn = !empty($lockedBooking->actual_check_in_at) && Schema::hasColumn('bookings', 'actual_check_in_at');

            // 1. Calculate or use manual days
            if (isset($options['days']) && is_numeric($options['days']) && (int) $options['days'] > 0) {
                $days = (int) $options['days'];
            } elseif ($hasActualCheckIn) {
                $inTime = Carbon::parse($lockedBooking->actual_check_in_at);
                $days = static::calculateHotelNights($inTime, $now);
            } else {
                $checkInDate = Carbon::parse($lockedBooking->check_in);
                $checkOutDate = Carbon::parse($lockedBooking->check_out);
                $days = max(1, (int) $checkInDate->diffInDays($checkOutDate));
            }

            // 2. Calculate or use manual nightly rate
            if (isset($options['nightly_rate']) && is_numeric($options['nightly_rate']) && (float) $options['nightly_rate'] >= 0) {
                $nightlyRate = (float) $options['nightly_rate'];
            } else {
                $nightlyRate = static::getNightlyRate($lockedBooking);
            }

            // 3. Room amount = days * nightly rate (or manual room_amount if specified)
            if (isset($options['room_amount']) && is_numeric($options['room_amount']) && (float) $options['room_amount'] >= 0) {
                $roomAmount = (float) $options['room_amount'];
            } elseif ($hasActualCheckIn || isset($options['days']) || isset($options['nightly_rate'])) {
                $roomAmount = round($days * $nightlyRate, 2);
            } else {
                $roomAmount = (float) $lockedBooking->total_price;
            }

            // Update booking actual check-out and recalculated amounts
            if (Schema::hasColumn('bookings', 'actual_check_out_at')) {
                $lockedBooking->actual_check_out_at = $now;
            }
            $lockedBooking->check_out = $now->toDateString();
            $lockedBooking->total_price = $roomAmount;
            if ($lockedBooking->currency === 'USD') {
                $lockedBooking->total_price_usd = $roomAmount;
            }
            $lockedBooking->status = 'checked_out';
            $lockedBooking->save();

            // Set room status to cleaning
            if ($lockedBooking->room) {
                $lockedBooking->room->update([
                    'status' => 'cleaning',
                    'needs_cleaning' => true,
                ]);
            }

            // Settle or create invoice
            $invoice = Invoice::where('hotel_id', $lockedBooking->hotel_id)
                ->where('booking_id', $lockedBooking->id)
                ->lockForUpdate()
                ->first();

            $serviceAmount = (float) ($options['service_amount'] ?? ($invoice?->service_amount ?? 0));
            $serviceDescription = $options['service_description'] ?? ($invoice?->service_description ?? null);
            $taxRate = (float) ($options['tax_rate'] ?? ($invoice?->tax_rate ?? 0));
            $taxName = $taxRate > 0 ? (trim($options['tax_name'] ?? ($invoice?->tax_name ?? '')) ?: 'ضريبة') : null;
            $taxAmount = round(($roomAmount + $serviceAmount) * $taxRate / 100, 2);
            $grandTotal = $roomAmount + $serviceAmount + $taxAmount;
            $paymentMethod = $options['payment_method'] ?? 'cash';
            $transactionId = $options['transaction_id'] ?? null;
            $currency = $lockedBooking->currency ?: ($invoice?->currency ?? 'SYP');

            $invData = [
                'hotel_id' => $lockedBooking->hotel_id,
                'booking_id' => $lockedBooking->id,
                'room_id' => $lockedBooking->room_id,
                'amount' => $roomAmount,
                'currency' => $currency,
                'service_amount' => $serviceAmount,
                'service_description' => $serviceDescription,
                'tax_name' => $taxName,
                'tax_rate' => $taxRate,
                'tax' => $taxAmount,
                'total' => $grandTotal,
                'status' => 'paid',
            ];

            if (Schema::hasColumn('invoices', 'nights')) {
                $invData['nights'] = $days;
            }
            if (Schema::hasColumn('invoices', 'nightly_rate')) {
                $invData['nightly_rate'] = $nightlyRate;
            }

            if (! $invoice) {
                $invData['invoice_number'] = 'INV-' . strtoupper(Str::random(8));
                $invoice = Invoice::create($invData);
            } else {
                $invoice->update($invData);
            }

            // Register Payment record
            if (Schema::hasTable('payments')) {
                if (! $invoice->payments()->where('status', 'success')->exists()) {
                    Payment::create([
                        'invoice_id' => $invoice->id,
                        'transaction_id' => $transactionId,
                        'payment_method' => $paymentMethod,
                        'amount' => $grandTotal,
                        'currency' => $currency,
                        'status' => 'success',
                        'paid_at' => $now,
                    ]);
                } else {
                    $payment = $invoice->payments()->where('status', 'success')->latest('id')->first();
                    if ($payment) {
                        $payment->update([
                            'amount' => $grandTotal,
                            'currency' => $currency,
                        ]);
                    }
                }
            }

            // Register / Update Income Accounting Entry in Ledger
            if (Schema::hasTable('accounts')) {
                $account = Account::where('invoice_id', $invoice->id)->first();
                if ($account) {
                    $account->update([
                        'amount' => $grandTotal,
                        'currency' => $currency,
                        'title' => 'تحصيل فاتورة ' . ($invoice->invoice_number ?: ('#' . $invoice->id)),
                        'transaction_date' => $now->toDateString(),
                    ]);
                } else {
                    Account::create([
                        'hotel_id' => $invoice->hotel_id,
                        'invoice_id' => $invoice->id,
                        'type' => 'income',
                        'title' => 'تحصيل فاتورة ' . ($invoice->invoice_number ?: ('#' . $invoice->id)),
                        'amount' => $grandTotal,
                        'currency' => $currency,
                        'transaction_date' => $now->toDateString(),
                        'notes' => 'حجز رقم ' . $lockedBooking->id . ' للنزيل ' . ($lockedBooking->guest?->name ?? 'نزيل'),
                    ]);
                }
            }

            return [
                'booking' => $lockedBooking,
                'invoice' => $invoice,
                'days' => $days,
                'nightly_rate' => $nightlyRate,
                'room_amount' => $roomAmount,
            ];
        });
    }
}
