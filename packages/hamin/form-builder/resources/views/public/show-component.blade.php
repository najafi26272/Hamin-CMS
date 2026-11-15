{{-- صفحه عمومی فرم برای لایوت‌های component (config: public.layout_type = component)، مثل <x-app-layout> --}}
<x-dynamic-component :component="$layout" :title="$form->title">
    @include('form-builder::public.content')
</x-dynamic-component>
