@extends('errors.layout')

@section('title', 'حدث خطأ في النظام - يرجى مراجعة مشغل البرنامج')
@section('code', '500 - تنبيه في المعالجة')
@section('icon', '⚠️')
@section('icon-bg', 'bg-danger-subtle text-danger')

@section('message')
نعتذر، حدث خطأ غير متوقع أثناء معالجة العملية المطلوبة وتم إيقافها للحفاظ على استقرار النظام وحماية البيانات. الأمر خاطئ أو غير مكتمل، يرجى مراجعة مشغل البرنامج للتحقق من المشكلة.
@endsection

