@extends('errors.layout')

@section('title', 'غير مصرح لك بتنفيذ هذه العملية')
@section('code', '403 - صلاحيات غير كافية')
@section('icon', '🔒')
@section('icon-bg', 'bg-secondary-subtle text-secondary')

@section('message')
عذراً، هذا الإجراء أو القسم مخصص لمستخدمين بصلاحيات أعلى أو دور مختلف. إذا كنت تحتاج للوصول إلى هذه الوظيفة، يرجى مراجعة مشغل البرنامج أو مدير النظام لمنحك الصلاحيات اللازمة.
@endsection

