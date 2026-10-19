@extends('errors.layout')

@section('title', 'تنبيه: تعارض أو إجراء تم تنفيذه مسبقاً')
@section('code', '409 - تعارض في الحالة')
@section('icon', '⚠️')
@section('icon-bg', 'bg-warning-subtle text-warning')

@section('message')
{{ !empty($exception?->getMessage()) ? $exception->getMessage() : ($message ?? 'هذا الإجراء تم تنفيذه مسبقاً أو غير متاح في الحالة الحالية. يرجى مراجعة مشغل البرنامج في حال وجود استفسار.') }}
@endsection

