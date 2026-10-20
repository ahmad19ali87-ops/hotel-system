<?php

namespace App\Services;

class CaptchaService
{
    /**
     * Generate a new math captcha challenge and store hashed solution in session.
     */
    public static function generate(): array
    {
        $ops = ['+', '-', '×'];
        $op = $ops[array_rand($ops)];
        
        if ($op === '+') {
            $a = random_int(3, 12);
            $b = random_int(2, 9);
            $answer = $a + $b;
            $question = "ما حاصل جمع {$a} + {$b} ؟";
        } elseif ($op === '-') {
            $a = random_int(10, 20);
            $b = random_int(1, 9);
            $answer = $a - $b;
            $question = "ما حاصل طرح {$a} - {$b} ؟";
        } else {
            $a = random_int(2, 8);
            $b = random_int(2, 6);
            $answer = $a * $b;
            $question = "ما ناتج ضرب {$a} × {$b} ؟";
        }

        $token = hash('sha256', (string)$answer . config('app.key'));
        session(['math_captcha_token' => $token]);

        return [
            'question' => $question,
            'hint' => 'أدخل الإجابة بالأرقام للتحقق من أنك إنسان',
        ];
    }

    /**
     * Verify the user's submitted captcha answer.
     */
    public static function verify(?string $userInput): bool
    {
        if ($userInput === null || $userInput === '') {
            return false;
        }

        // Convert Eastern Arabic numerals (٠-٩) to Western (0-9)
        $cleanInput = strtr(trim($userInput), [
            '٠'=>'0', '١'=>'1', '٢'=>'2', '٣'=>'3', '٤'=>'4',
            '٥'=>'5', '٦'=>'6', '٧'=>'7', '٨'=>'8', '٩'=>'9'
        ]);

        $storedHash = session('math_captcha_token');
        if (!$storedHash) {
            return false;
        }

        $inputHash = hash('sha256', $cleanInput . config('app.key'));
        $isValid = hash_equals($storedHash, $inputHash);

        // Invalidate immediately after checking so it cannot be re-used
        session()->forget('math_captcha_token');

        return $isValid;
    }
}

