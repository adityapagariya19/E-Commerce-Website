@extends('layouts.store')
@section('title', 'Help Centre – '.setting('store_name', 'JB Optician'))
@section('content')
<div class="wrap help" style="max-width:960px;padding-bottom:30px">
  <div style="background:#f8f9fa;text-align:center;padding:14px;margin-top:20px"><h1 style="font-weight:400;margin:0;font-size:30px">Help Centre</h1><small>How can we assist you today?</small></div>
  <input class="search" id="help-search" type="search" placeholder="Search Help Centre..." aria-label="Search Help Centre">
  <h2>Help Topics</h2>
  <div class="topics">@foreach($topics as $t)<a href="#topic-{{ $t->id }}">{{ $t->title }}</a>@endforeach</div>
  @foreach($topics as $t)
    <section id="topic-{{ $t->id }}"><h2>{{ $t->title }}</h2>
      @foreach($t->faqs as $f)<div data-faq-item><a class="q" href="#faq-{{ $f->id }}" data-faq="faq-{{ $f->id }}">{{ $f->question }}</a>
        <div class="ans" id="faq-{{ $f->id }}">{{ $f->answer ?: 'Please contact us and we will be happy to help with this.' }}</div></div>@endforeach
    </section>
  @endforeach
  <h2>Contact Us</h2>
  @php($mail = setting('help_email', setting('store_email')))@php($tel = setting('help_phone', setting('store_phone')))
  @if($mail)<a class="btn btn--block btn--rect" style="background:#007bff;margin-bottom:8px" href="mailto:{{ $mail }}">Email Us</a>@endif
  @if($tel)<a class="btn btn--block btn--rect btn--grey" style="margin-bottom:8px" href="tel:{{ preg_replace('/[^0-9+]/', '', $tel) }}">Call Us</a>@endif
  <a class="btn btn--block btn--rect btn--green" href="{{ route('contact') }}">Visit Our Contact Page</a>
  <div id="feedback" style="background:#f8f8f8;padding:30px;margin-top:26px"><h2 style="text-align:center;font-weight:400">Feedback</h2>
    <form class="card-form" method="POST" action="{{ route('help.feedback') }}" novalidate>@csrf<input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
      <div class="field"><label for="f-name">Name:</label><input id="f-name" name="name" value="{{ old('name') }}" required>@error('name')<div class="errors">{{ $message }}</div>@enderror</div>
      <div class="field"><label for="f-email">Email:</label><input id="f-email" type="email" name="email" value="{{ old('email') }}" required>@error('email')<div class="errors">{{ $message }}</div>@enderror</div>
      <div class="field"><label for="f-msg">Message:</label><textarea id="f-msg" name="message" required>{{ old('message') }}</textarea>@error('message')<div class="errors">{{ $message }}</div>@enderror</div>
      <button class="btn btn--block btn--rect" type="submit">Submit Form</button></form></div>
</div>
@endsection
