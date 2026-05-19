<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password GudangSafe</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 500px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #2E7D32;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 24px;
        }
        .header p {
            color: #ffffff99;
            margin: 8px 0 0 0;
            font-size: 14px;
        }
        .body {
            padding: 32px;
        }
        .body p {
            color: #333333;
            font-size: 15px;
            line-height: 1.6;
            margin: 0 0 16px 0;
        }
        .otp-box {
            background-color: #f0f7f0;
            border: 2px dashed #2E7D32;
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            margin: 24px 0;
        }
        .otp-label {
            color: #666666;
            font-size: 13px;
            margin: 0 0 8px 0;
        }
        .otp-code {
            color: #2E7D32;
            font-size: 40px;
            font-weight: bold;
            letter-spacing: 8px;
            margin: 0;
        }
        .otp-expire {
            color: #999999;
            font-size: 12px;
            margin: 8px 0 0 0;
        }
        .warning {
            background-color: #fff8e1;
            border-left: 4px solid #ffc107;
            padding: 12px 16px;
            border-radius: 4px;
            margin: 16px 0;
        }
        .warning p {
            color: #795548;
            font-size: 13px;
            margin: 0;
        }
        .footer {
            background-color: #f5f5f5;
            padding: 20px;
            text-align: center;
        }
        .footer p {
            color: #999999;
            font-size: 12px;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏭 GudangSafe</h1>
            <p>Sistem Monitoring Gudang IoT</p>
        </div>
        <div class="body">
            <p>Halo, <strong>{{ $name }}</strong>!</p>
            <p>Kami menerima permintaan reset password untuk akun GudangSafe Anda. Gunakan kode OTP berikut untuk mereset password:</p>

            <div class="otp-box">
                <p class="otp-label">Kode OTP Reset Password</p>
                <p class="otp-code">{{ $otp }}</p>
                <p class="otp-expire">⏱ Berlaku selama <strong>10 menit</strong></p>
            </div>

            <div class="warning">
                <p>⚠️ Jangan bagikan kode ini kepada siapapun, termasuk tim GudangSafe. Kami tidak pernah meminta kode OTP Anda.</p>
            </div>

            <p>Jika Anda tidak meminta reset password, abaikan email ini. Password Anda tidak akan berubah.</p>
            <p>Terima kasih,<br><strong>Tim GudangSafe</strong><br>Toko Pertanian Bumi Jaya</p>
        </div>
        <div class="footer">
            <p>© 2025 GudangSafe · Jl. Urip Sumoharjo No.74, Tanggul, Kabupaten Jember, Jawa Timur</p>
        </div>
    </div>
</body>
</html>
