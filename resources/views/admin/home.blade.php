@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-6 align-self-left">
            <h4 class="mt-3">Admin</h4>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div id="admin-market-history"></div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div id="admin-sign-in-history" data-user="{{auth()->user()->id}}"></div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div id="admin-register-history" data-user="{{auth()->user()->id}}"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-xl-12">
            <div id="admin-chat-messages" data-user="{{auth()->user()->id}}"></div>
        </div>
    </div>
    <div class="row">
        <div class="col-xl-12">
            <div id="admin-chat"></div>
        </div>
    </div>
</div>
@endsection

@push('head')
    <script src={{mix('js/admin-chat-messages.js')}} type="text/javascript"></script>
    <script src={{mix('js/admin-site-stats-components.js')}} type="text/javascript"></script>
@endpush

@push('scripts')
    <script>
        renderChatMessages('admin-chat-messages');
        renderSignIn('admin-sign-in-history');
        renderRegister('admin-register-history');
    </script>
@endpush
