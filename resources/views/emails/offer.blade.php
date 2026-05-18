<!DOCTYPE html>
<html dir="rtl">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f9fafb; padding: 20px; }
        .container { max-width: 500px; margin: 0 auto; background: white; border-radius: 12px; padding: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { color: #d97706; font-size: 24px; }
        .content { color: #374151; line-height: 1.8; font-size: 16px; }
        .offer-box { background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; padding: 15px; margin: 15px 0; }
        .button { display: inline-block; background: #d97706; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; margin: 20px 0; }
        .footer { text-align: center; color: #9ca3af; font-size: 12px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎁 عرض خاص لك</h1>
        </div>
        <div class="content">
            <p>أهلاً {{ $name }}،</p>
            <p>يسعدنا أن نقدم لك العرض التالي:</p>
            <div class="offer-box">
                {!! nl2br(e($offer)) !!}
            </div>
            <p>العرض ساري لفترة محدودة. للاستفادة، تواصل معنا الآن!</p>
            <p style="text-align:center;">
                <a href="{{ config('app.url') }}" class="button">اطلب الآن</a>
            </p>
            <p>تحياتنا،<br>فريق AI Sales Agent</p>
        </div>
        <div class="footer">
            <p>هذه رسالة تلقائية من AI Sales Agent</p>
        </div>
    </div>
</body>
</html>
