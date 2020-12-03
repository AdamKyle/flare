@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-6 align-self-left">
            <h4 class="mt-3">Admin</h4>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-xl-6">
            <div class="card">
                <div class="card-body">
                    <div id="admin-market-history"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xl-12">
            <div id="admin-chat"></div>
        </div>
    </div>
</div>
@endsection
