<!DOCTYPE html>
<html dir="rtl">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f9fafb; padding: 20px; }
        .container { max-width: 500px; margin: 0 auto; background: white; border-radius: 12px; padding: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { color: #1a56db; font-size: 24px; }
        .content { color: #374151; line-height: 1.8; font-size: 16px; }
        .button { display: inline-block; background: #1a56db; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; margin: 20px 0; }
        .footer { text-align: center; color: #9ca3af; font-size: 12px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>مرحباً بك {{ $name }} 🙌</h1>
        </div>
        <div class="content">
            <p>شكراً لتواصلك معنا!</p>
            <p>سعيدين بأنك معنا. فريقنا هيبدأ يشتغل على طلبك فوراً.</p>
            <p>هنتواصل معاك قريباً بكل التفاصيل والعروض المناسبة لك.</p>
            <p style="text-align:center;">
                <a href="{{ config('app.url') }}" class="button">زور موقعنا</a>
            </p>
            <p>تحياتنا،<br>فريق AI Sales Agent</p>
        </div>
        <div class="footer">
            <p>هذه رسالة تلقائية من AI Sales Agent</p>
        </div>
    </div>
</body>
</html>
