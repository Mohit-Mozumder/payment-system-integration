<!DOCTYPE html>
<html>
<head>
    <title>Donation App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <h2 class="mb-4">Make a Donation</h2>
                
                @if(session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif
                
                @if(session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif
                
                <form method="POST" action="{{ route('donations.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="amount" class="form-label">Amount ($)</label>
                        <input type="number" class="form-control" id="amount" name="amount" min="1" step="0.01" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="card_number" class="form-label">Card Number</label>
                        <input type="text" class="form-control" id="card_number" name="card_number" placeholder="4242424242424242" required>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="exp_date" class="form-label">Expiration Date (MMYY)</label>
                            <input type="text" class="form-control" id="exp_date" name="exp_date" placeholder="1225" required>
                        </div>
                        <div class="col-md-6">
                            <label for="cvv" class="form-label">CVV</label>
                            <input type="text" class="form-control" id="cvv" name="cvv" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Donate</button>
                    <a href="{{ route('donations.list') }}" class="btn btn-secondary">View Donations</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>