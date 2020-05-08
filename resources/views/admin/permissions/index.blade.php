@extends('varbox::layouts.default')

@section('title', $title)

@section('content')
    <div class="row row-cards">
        <div class="col-lg-3">
            @permission('permissions-add')
                @include('varbox::buttons.add', ['url' => route('admin.permissions.create')])
            @endpermission

            @include('varbox::admin.permissions._filter')
        </div>
        <div class="col-lg-9">
            @include('varbox::admin.permissions._table')

            {!! $items->links('varbox::pagination', request()->query()) !!}
        </div>
    </div>
@endsection
