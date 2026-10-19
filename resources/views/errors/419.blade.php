@extends('errors.layout')

@section('title', 'انتهت مهلة الجلسة - أمان النظام')
@section('code', '419 - انتهاء الجلسة')
@section('icon', '⏱️')
@section('icon-bg', 'bg-info-subtle text-info')

@section('message')
انتهت مهلة الجلسة الأمنية الخاصة بك بسبب عدم التفاعل لفترة معينة. يرجى تحديث الصفحة أو تسجيل الدخول مجدداً للمتابعة بأمان.
@endsection

