<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Cancelled</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
    <div class="min-h-screen flex items-center justify-center bg-gray-100">
        <div class="bg-white p-8 rounded-xl shadow-lg max-w-md w-full text-center">
            <div class="mb-4">
                <svg class="w-16 h-16 text-yellow-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">⚠️ Payment Cancelled</h1>
            <p class="text-gray-600 mb-4">Your payment was cancelled. You can try again anytime.</p>
            <div class="space-y-3">
                <a href="/invoices" class="block w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition">
                    View Invoices
                </a>
                <a href="/dashboard" class="block w-full bg-gray-200 text-gray-700 py-2 rounded-lg hover:bg-gray-300 transition">
                    Go to Dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>
