@extends('errors.layout')

@section('title', 'العملية غير معرفة أو الصفحة غير موجودة')
@section('code', '404 - مسار غير معرف')
@section('icon', '🔍')
@section('icon-bg', 'bg-warning-subtle text-warning')

@section('message')
نعتذر، الإجراء أو الرابط الذي حاولت الوصول إليه غير معرف في النظام أو تم نقله. يرجى التأكد من صحة العملية أو مراجعة مشغل البرنامج.
@endsection

