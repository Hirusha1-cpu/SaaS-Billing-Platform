<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
    <div class="min-h-screen flex items-center justify-center bg-gray-100">
        <div class="bg-white p-8 rounded-xl shadow-lg max-w-md w-full text-center">
            <div class="mb-4">
                <svg class="w-16 h-16 text-green-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">✅ Payment Successful!</h1>
            <p class="text-gray-600 mb-4">Thank you for your payment. Your invoice has been paid.</p>
            <p class="text-sm text-gray-500 mb-6">Session ID: {{ request('session_id') }}</p>
            <div class="space-y-3">
                <a href="/dashboard" class="block w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition">
                    Go to Dashboard
                </a>
                <a href="/invoices" class="block w-full bg-gray-200 text-gray-700 py-2 rounded-lg hover:bg-gray-300 transition">
                    View Invoices
                </a>
            </div>
        </div>
    </div>
</body>
</html>
