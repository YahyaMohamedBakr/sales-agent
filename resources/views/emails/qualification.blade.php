<!DOCTYPE html>
<html dir="rtl">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f9fafb; padding: 20px; }
        .container { max-width: 500px; margin: 0 auto; background: white; border-radius: 12px; padding: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { color: #059669; font-size: 24px; }
        .content { color: #374151; line-height: 1.8; font-size: 16px; }
        .check { color: #059669; font-size: 20px; }
        .footer { text-align: center; color: #9ca3af; font-size: 12px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ تم تسجيل بياناتك</h1>
        </div>
        <div class="content">
            <p>أهلاً {{ $name }}،</p>
            <p class="check">✔ تم استلام بياناتك بنجاح.</p>
            <p>فريق المبيعات لدينا سيراجع طلبك ويتواصل معك في أقرب وقت ممكن.</p>
            <p>إذا كان عندك أي استفسار إضافي، لا تتردد في الرد على هذه الرسالة.</p>
            <p>تحياتنا،<br>فريق AI Sales Agent</p>
        </div>
        <div class="footer">
            <p>هذه رسالة تلقائية من AI Sales Agent</p>
        </div>
    </div>
</body>
</html>
