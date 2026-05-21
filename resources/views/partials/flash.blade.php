@if (session('license_warning'))
    <div class="alert alert-warning d-flex align-items-center">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <div>{{ session('license_warning') }}</div>
    </div>
@endif

@if (session('status'))
    <div class="alert alert-success d-flex align-items-center">
        <i class="bi bi-check-circle me-2"></i>
        <div>{{ session('status') }}</div>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger d-flex align-items-center">
        <i class="bi bi-x-circle me-2"></i>
        <div>{{ $errors->first() }}</div>
    </div>
@endif
