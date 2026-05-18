import { useState, FormEvent } from 'react';
import { router } from '@inertiajs/react';

interface Props {
    errors?: Record<string, string>;
}

export default function Login({ errors: initialErrors }: Props) {
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [remember, setRemember] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>(initialErrors || {});

    function handleSubmit(e: FormEvent) {
        e.preventDefault();

        router.post('/login', {
            email,
            password,
            remember,
        });
    }

    return (
        <div className="min-h-screen flex items-center justify-center bg-gray-100 p-4" dir="rtl">
            <form onSubmit={handleSubmit} className="bg-white rounded-2xl shadow-lg p-8 w-full max-w-md">
                <h1 className="text-2xl font-bold text-center mb-6">تسجيل الدخول</h1>

                {errors.email && (
                    <div className="bg-red-50 text-red-700 p-3 rounded-lg mb-4 text-sm">
                        {errors.email}
                    </div>
                )}

                <div className="mb-4">
                    <label className="block text-sm font-medium text-gray-700 mb-1">البريد الإلكتروني</label>
                    <input
                        type="email"
                        value={email}
                        onChange={e => setEmail(e.target.value)}
                        className="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                        required
                        autoFocus
                    />
                </div>

                <div className="mb-4">
                    <label className="block text-sm font-medium text-gray-700 mb-1">كلمة المرور</label>
                    <input
                        type="password"
                        value={password}
                        onChange={e => setPassword(e.target.value)}
                        className="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                        required
                    />
                </div>

                <div className="mb-6">
                    <label className="flex items-center gap-2 text-sm text-gray-600">
                        <input
                            type="checkbox"
                            checked={remember}
                            onChange={e => setRemember(e.target.checked)}
                            className="rounded border-gray-300"
                        />
                        تذكرني
                    </label>
                </div>

                <button
                    type="submit"
                    className="w-full bg-blue-600 text-white font-medium py-2.5 rounded-lg hover:bg-blue-700 transition"
                >
                    دخول
                </button>
            </form>
        </div>
    );
}
