'use client';

import { FormEvent, useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '../context/AuthContext';

export default function ChangePasswordPage() {
  const { user, token, isLoading, logout, setToken } = useAuth();
  const router = useRouter();
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmation, setConfirmation] = useState('');
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    if (!isLoading && !token) router.replace('/login');
    if (!isLoading && user && !user.must_change_password) router.replace('/me');
  }, [isLoading, token, user, router]);

  async function submit(event: FormEvent) {
    event.preventDefault();
    setError('');
    setMessage('');
    if (newPassword !== confirmation) {
      setError('A confirmação da nova senha não confere.');
      return;
    }
    setSaving(true);
    try {
      const response = await fetch('/api/auth/change-initial-password', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
        body: JSON.stringify({ current_password: currentPassword, new_password: newPassword, new_password_confirmation: confirmation }),
      });
      const data = await response.json();
      if (!response.ok) {
        setError(data.message || 'Não foi possível alterar a senha.');
        return;
      }
      setMessage('Senha alterada com sucesso. Redirecionando…');
      setToken(data.token);
      setTimeout(() => router.replace('/me'), 500);
    } catch {
      setError('Não foi possível conectar ao servidor.');
    } finally {
      setSaving(false);
    }
  }

  if (isLoading || !token) return null;

  return <main className="min-h-screen bg-slate-50 flex items-center justify-center p-5">
    <form onSubmit={submit} className="w-full max-w-md rounded-2xl bg-white p-7 shadow-sm border border-slate-200 space-y-5">
      <div><h1 className="text-2xl font-bold text-slate-900">Troca obrigatória de senha</h1><p className="mt-2 text-sm text-slate-600">Para proteger sua conta, defina uma nova senha antes de acessar o sistema.</p></div>
      {error && <p role="alert" className="rounded-lg bg-red-50 p-3 text-sm text-red-700">{error}</p>}
      {message && <p role="status" className="rounded-lg bg-green-50 p-3 text-sm text-green-700">{message}</p>}
      <label className="block text-sm font-medium">Senha atual<input required type="password" value={currentPassword} onChange={e => setCurrentPassword(e.target.value)} className="mt-1 w-full rounded-lg border p-3" /></label>
      <label className="block text-sm font-medium">Nova senha<input required type="password" value={newPassword} onChange={e => setNewPassword(e.target.value)} className="mt-1 w-full rounded-lg border p-3" aria-describedby="password-rule" /></label>
      <p id="password-rule" className="text-xs text-slate-500">Use ao menos 8 caracteres, com maiúscula, minúscula, número e símbolo.</p>
      <label className="block text-sm font-medium">Confirme a nova senha<input required type="password" value={confirmation} onChange={e => setConfirmation(e.target.value)} className="mt-1 w-full rounded-lg border p-3" /></label>
      <button disabled={saving} className="w-full rounded-lg bg-emerald-700 py-3 font-semibold text-white disabled:opacity-60">{saving ? 'Salvando…' : 'Alterar senha'}</button>
      <button type="button" onClick={() => { logout(); router.replace('/login'); }} className="w-full text-sm text-slate-600 underline">Sair</button>
    </form>
  </main>;
}
