@extends('layouts.admin')
@section('title','Help question')
@section('content')<h1>{{ $faq->exists ? 'Edit question' : 'Add question' }}</h1><form class="card" style="max-width:720px" method="POST" action="{{ $faq->exists ? route('admin.faqs.update', $faq) : route('admin.faqs.store') }}">@csrf @if($faq->exists) @method('PUT') @endif
<div class="f"><label>Topic *</label><select name="faq_topic_id">@foreach($topics as $t)<option value="{{ $t->id }}" @selected(old('faq_topic_id', $faq->faq_topic_id)==$t->id)>{{ $t->title }}</option>@endforeach</select></div>
<div class="f"><label>Question *</label><input type="text" name="question" value="{{ old('question', $faq->question) }}" required></div><div class="f"><label>Answer</label><textarea name="answer">{{ old('answer', $faq->answer) }}</textarea></div>
<div class="f"><label>Order</label><input type="number" min="0" name="position" value="{{ old('position', $faq->position ?? 0) }}"></div><label style="font-weight:400"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $faq->is_active))> Active</label><br><br><button class="btn">Save</button></form>@endsection
