@extends('layouts.legacy')

@section('titulo_browser',$aux=trans('auth.register'). ' - LookCrim')

@section('conteudo')

    <div class="row justify-content-center">
        <div class="col-md-8 with-padding">
            <div class="card card-lookcrim">
                <div class="card-header-lookcrim">@lang('pages.notification')</div>

                <div class="card-body">
                    <div class="form-group row">
                        <label for="name" class="col-md-4 col-form-label text-md-right">{{ $text }}</label>
                    </div>

                    <div class="form-group row mb-0">
                        <div class="col-md-12 center">
                            <a href="{{url('')}}" class="btn btn-lookcrim" style="padding-left: 20px;">
                                @lang('buttons.back')
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
