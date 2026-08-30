<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirecting to WHMCS...</title>
    <style>
        body {
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: #0f172a;
            color: #e2e8f0;
            font-family: system-ui, -apple-system, sans-serif;
        }
        .loader {
            text-align: center;
        }
        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid rgba(255,255,255,0.1);
            border-top-color: #22d3ee;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 16px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="loader">
        <div class="spinner"></div>
        <p>Logging you into WHMCS...</p>
    </div>

    <script>
        (function() {
            var ssoUrl = @json($ssoUrl);
            var targetUrl = @json($targetUrl);

            var iframe = document.createElement('iframe');
            iframe.style.display = 'none';
            iframe.src = ssoUrl;
            document.body.appendChild(iframe);

            iframe.onload = function() {
                setTimeout(function() {
                    window.location.href = targetUrl;
                }, 1500);
            };

            setTimeout(function() {
                window.location.href = targetUrl;
            }, 4000);
        })();
    </script>
</body>
</html>
