{{-- صفحه عمومی فرم برای لایوت‌هایی که با @extends کار می‌کنند (config: public.layout_type = extends) --}}
@extends($layout)

@section('title', $form->title)

@section($section)
    @include('form-builder::public.content')
@endsection
