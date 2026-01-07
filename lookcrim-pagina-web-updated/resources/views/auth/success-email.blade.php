@extends('layouts.layout')

@section('titulo_browser',$aux=trans('auth.edit-profile'). ' - LookCrim')

@section('conteudo')

    <div class="row justify-content-center">
        <div class="col-md-8 with-padding">
            <div class="card card-lookcrim">
                <div class="card-header-lookcrim">@lang('auth.edit-profile')</div>

                <div class="card-body">
                    <div class="form-group row">
                        <label for="name" class="col-md-4 col-form-label text-md-right">@lang('passwords.success-mail')</label>
                    </div>

                    <div class="form-group row mb-0">
                        <div class="col-md-12 center">
                            @can('view_page_management')
                                <a href="{{url('/user/management')}}" class="btn btn-lookcrim" style="padding-left: 20px;">
                                    @lang('buttons.back')
                                </a>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </br>

@endsection
