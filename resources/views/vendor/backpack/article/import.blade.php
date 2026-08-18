@extends(backpack_view('blank'))

@php
    $widgets['before_content'][] = [
        'type' => 'div',
        'class' => 'row',
        'content' => [
            [
                'type' => 'progress',
                'class' => 'card text-white bg-primary',
                'description' => 'Article Import',
            ]
        ]
    ];
@endphp

@section('header')
    <section class="container-fluid">
        <h2>
            <span class="text-capitalize">{{ __('article.import.title') }}</span>
        </h2>
    </section>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(isset($errors) && $errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('article.import.process') }}" id="import-form">
                        @csrf

                        <input type="hidden" name="import_type" value="db">

                        <div class="form-group mb-3">
                            <label for="limit" class="form-label">Загальна кількість статей для імпорту (опціонально)</label>
                            <input 
                                type="number" 
                                class="form-control @error('limit') is-invalid @enderror" 
                                id="limit" 
                                name="limit" 
                                value="{{ old('limit') }}"
                                placeholder="Залиште порожнім для імпорту всіх статей"
                                min="1"
                                max="100000"
                            >
                            <small class="form-text text-muted">
                                Якщо не вказано, будуть імпортовані всі статті з бази даних
                            </small>
                            @error('limit')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="batch_limit" class="form-label">Кількість статей на батч</label>
                            <input 
                                type="number" 
                                class="form-control @error('batch_limit') is-invalid @enderror" 
                                id="batch_limit" 
                                name="batch_limit" 
                                value="{{ old('batch_limit', 100) }}"
                                min="1"
                                max="1000"
                            >
                            <small class="form-text text-muted">
                                Скільки статей завантажувати з БД за один раз (рекомендовано: 100)
                            </small>
                            @error('batch_limit')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary" id="import-submit-btn">
                                <i class="la la-upload"></i> {{ __('article.import.submit_button') }}
                            </button>
                            <a href="{{ backpack_url('article') }}" class="btn btn-secondary">
                                <i class="la la-times"></i> {{ __('article.import.cancel_button') }}
                            </a>
                        </div>
                        
                        <div id="import-status" class="mt-3" style="display: none;">
                            <div class="alert alert-info">
                                <i class="la la-spinner la-spin"></i> <span id="import-status-text">Обробка...</span>
                            </div>
                        </div>
                    </form>
                    
                    @php
                        $submitButtonText = __('article.import.submit_button');
                    @endphp
                    
                    <script>
                    var submitButtonText = {!! json_encode($submitButtonText) !!};
                    
                    document.addEventListener('DOMContentLoaded', function() {
                        const form = document.getElementById('import-form');
                        const submitBtn = document.getElementById('import-submit-btn');
                        const statusDiv = document.getElementById('import-status');
                        const statusText = document.getElementById('import-status-text');
                        
                        // Only DB import is supported - no need for type switching
                        
                        // Handle import form - prevent default submit and use AJAX
                        if (form) {
                            form.addEventListener('submit', function(e) {
                                e.preventDefault(); // Prevent default form submission
                                
                                // Show status
                                if (statusDiv) {
                                    statusDiv.style.display = 'block';
                                    statusText.textContent = 'Імпорт з бази даних... (це може зайняти багато часу)';
                                }
                                
                                // Disable submit button
                                if (submitBtn) {
                                    submitBtn.disabled = true;
                                    submitBtn.innerHTML = '<i class="la la-spinner la-spin"></i> Обробка...';
                                }
                                
                                // Hide previous alerts
                                const alerts = document.querySelectorAll('.alert');
                                alerts.forEach(alert => {
                                    if (alert.classList.contains('alert-success') || alert.classList.contains('alert-danger')) {
                                        alert.style.display = 'none';
                                    }
                                });
                                
                                // Prepare form data
                                const formData = new FormData(form);
                                
                                // Get CSRF token from form
                                const csrfToken = formData.get('_token');
                                
                                // Send AJAX request
                                fetch(form.action, {
                                    method: 'POST',
                                    body: formData,
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'X-CSRF-TOKEN': csrfToken
                                    }
                                })
                                .then(response => {
                                    // Check if response is JSON (AJAX) or HTML (redirect)
                                    const contentType = response.headers.get('content-type');
                                    
                                    if (contentType && contentType.includes('application/json')) {
                                        return response.json();
                                    }
                                    
                                    // If redirected or HTML response, reload page
                                    if (response.redirected || response.ok) {
                                        window.location.reload();
                                        return;
                                    }
                                    
                                    return response.text();
                                })
                                .then(data => {
                                    if (!data) return;
                                    
                                    // If JSON response
                                    if (typeof data === 'object') {
                                        if (data.success) {
                                            // Show success message and reload
                                            if (statusDiv) {
                                                statusDiv.style.display = 'block';
                                                statusText.textContent = data.message || 'Імпорт успішно завершено!';
                                                statusDiv.className = 'mt-3 alert alert-success';
                                            }
                                            
                                            // Reload page after 2 seconds to show flash message
                                            setTimeout(function() {
                                                window.location.reload();
                                            }, 2000);
                                        } else {
                                            // Show error message
                                            if (statusDiv) {
                                                statusDiv.style.display = 'block';
                                                statusText.textContent = data.message || 'Помилка під час імпорту';
                                                statusDiv.className = 'mt-3 alert alert-danger';
                                            }
                                            
                                            // Re-enable submit button
                                            if (submitBtn) {
                                                submitBtn.disabled = false;
                                                submitBtn.innerHTML = '<i class="la la-upload"></i> ' + submitButtonText;
                                            }
                                        }
                                    } else {
                                        // HTML response - reload page
                                        window.location.reload();
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    if (statusDiv) {
                                        statusDiv.style.display = 'block';
                                        statusText.textContent = 'Помилка: ' + error.message;
                                        statusDiv.className = 'mt-3 alert alert-danger';
                                    }
                                    
                                    // Re-enable submit button
                                    if (submitBtn) {
                                        submitBtn.disabled = false;
                                        submitBtn.innerHTML = '<i class="la la-upload"></i> ' + submitButtonText;
                                    }
                                });
                            });
                        }
                    });
                    </script>
                </div>
            </div>
        </div>
    </div>
@endsection

