@extends('layouts.site')

@section('title', 'پویش و رویداد')

@section('content')
    <div class="container page">
        <div class="page-head">
            <div>
                <h1>پویش و رویداد</h1>
                <p>نمایشگاه‌ها، جشنواره‌ها و برنامه‌های مجمع هنری هامین</p>
            </div>
        </div>

        <nav class="tabs">
            <a href="{{ route('site.events') }}" @class(['tab', 'active' => ! $past])>پیش رو و در حال برگزاری</a>
            <a href="{{ route('site.events', ['show' => 'past']) }}" @class(['tab', 'active' => $past])>برگزار شده</a>
        </nav>

        @if ($events->isEmpty())
            <div class="empty">
                <x-site.icon name="calendar"/>
                <div>{{ $past ? 'رویداد برگزارشده‌ای وجود ندارد.' : 'در حال حاضر رویداد پیش رویی نداریم.' }}</div>
            </div>
        @else
            <div class="grid grid-4">
                @foreach ($events as $event)
                    @include('site.partials.event-card')
                @endforeach
            </div>

            {{ $events->links('site.partials.pagination') }}
        @endif
    </div>


    <select name="input_12" id="input_2_12" class="large gfield_select" aria-required="true" aria-invalid="false">
        <option value="" class="gf_placeholder">-- انتخاب کنید --</option>
        <option value="آشار">آشار</option>
        <option value="ادیمی">ادیمی</option>
        <option value="اسفندک">اسفندک</option>
        <option value="اسماعیل‌آباد">اسماعیل‌آباد</option>
        <option value="اسپکه">اسپکه</option>
        <option value="ایرانشهر">ایرانشهر</option>
        <option value="بزمان">بزمان</option>
        <option value="بمپور">بمپور</option>
        <option value="بنت">بنت</option>
        <option value="بنجار">بنجار</option>
        <option value="بندر بریس">بندر بریس</option>
        <option value="جالق">جالق</option>
        <option value="جزینک">جزینک</option>
        <option value="خاش">خاش</option>
        <option value="ده رئیس">ده رئیس</option>
        <option value="دوست محمد">دوست محمد</option>
        <option value="راسک">راسک</option>
        <option value="ریگ ملک">ریگ ملک</option>
        <option value="زابل">زابل</option>
        <option value="زاهدان">زاهدان</option>
        <option value="زرآباد">زرآباد</option>
        <option value="زهک">زهک</option>
        <option value="ساربوک">ساربوک</option>
        <option value="سراوان">سراوان</option>
        <option value="سرباز">سرباز</option>
        <option value="سرجنگل">سرجنگل</option>
        <option value="سوران">سوران</option>
        <option value="سیب">سیب</option>
        <option value="سیرکان">سیرکان</option>
        <option value="شهر جدید تیس">شهر جدید تیس</option>
        <option value="شهر جدید رامشار">شهر جدید رامشار</option>
        <option value="علی اکبر">علی اکبر</option>
        <option value="فنوج">فنوج</option>
        <option value="قاسم‌آباد">قاسم‌آباد</option>
        <option value="قرقری">قرقری</option>
        <option value="قصرقند">قصرقند</option>
        <option value="لادیز">لادیز</option>
        <option value="محمدآباد">محمدآباد</option>
        <option value="محمدان">محمدان</option>
        <option value="محمدی">محمدی</option>
        <option value="مهرستان">مهرستان</option>
        <option value="میرجاوه">میرجاوه</option>
        <option value="نصرت آباد">نصرت آباد</option>
        <option value="نوک آباد">نوک آباد</option>
        <option value="نگور">نگور</option>
        <option value="نیک شهر">نیک شهر</option>
        <option value="هیدوچ">هیدوچ</option>
        <option value="پارود">پارود</option>
        <option value="پلان">پلان</option>
        <option value="پیشین">پیشین</option>
        <option value="چابهار">چابهار</option>
        <option value="چانف">چانف</option>
        <option value="چگردک">چگردک</option>
        <option value="کتیج">کتیج</option>
        <option value="کنارک">کنارک</option>
        <option value="گشت">گشت</option>
        <option value="گلمورتی">گلمورتی</option>
    </select>

@endsection
