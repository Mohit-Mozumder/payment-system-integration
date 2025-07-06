<!DOCTYPE html>
<html>
<head>
    <title>Donations List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Donations List</h2>
        
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
        
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Amount</th>
                    <th>Transaction ID</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($donations as $donation)
                <tr>
                    <td>{{ $donation->name }}</td>
                    <td>{{ $donation->phone }}</td>
                    <td>${{ number_format($donation->amount, 2) }}</td>
                    <td>{{ $donation->transaction_id }}</td>
                    <td>{{ ucfirst($donation->status) }}</td>
                    <td>
                    @if($donation->status !== 'refunded')
                    <form method="POST" action="{{ route('donations.refund', $donation) }}" onsubmit="return confirm('Are you sure you want to refund this donation?')">
                        @csrf
                        <button type="submit" class="btn btn-warning btn-sm">Refund to Card</button>
                    </form>
                    @else
                    <span class="badge bg-success">Refunded</span>
                    <span class="text-muted small">({{ $donation->refunded_at->format('m/d/Y') }})</span>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <a href="{{ route('donations.form') }}" class="btn btn-primary">Back to Donation Form</a>
    </div>
</body>
</html>