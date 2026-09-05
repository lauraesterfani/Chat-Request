"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { Bell, CheckCheck, Settings2, X } from "lucide-react";

type Notice = { id: string; title: string; body: string; link?: string | null; read_at?: string | null; created_at: string };
type Preference = { category: string; internal_enabled: boolean; email_enabled: boolean };

const preferenceLabels: Record<string, string> = {
  message: "Mensagens de atendimento",
  status: "Mudanças de etapa",
  request: "Requerimentos",
  document: "Documentos",
  workflow: "Encaminhamentos e decisões",
};

export default function NotificationBell() {
  const [count, setCount] = useState(0);
  const [open, setOpen] = useState(false);
  const [preferencesOpen, setPreferencesOpen] = useState(false);
  const [items, setItems] = useState<Notice[]>([]);
  const [preferences, setPreferences] = useState<Preference[]>([]);
  const [preferencesLoading, setPreferencesLoading] = useState(false);
  const token = typeof window !== "undefined" ? sessionStorage.getItem("jwt_token") : null;

  const load = async () => {
    if (!token) return;
    const response = await fetch("/api/notifications?per_page=5", { headers: { Authorization: `Bearer ${token}` } });
    if (!response.ok) return;
    const data = await response.json();
    setItems(data.data ?? []);
    const unread = await fetch("/api/notifications/count", { headers: { Authorization: `Bearer ${token}` } });
    if (unread.ok) setCount((await unread.json()).unread ?? 0);
  };

  useEffect(() => {
    load();
    const timer = window.setInterval(load, 15000);
    return () => window.clearInterval(timer);
  }, []);

  const markRead = async (id: string) => {
    if (!token) return;
    await fetch(`/api/notifications/${id}/read`, { method: "POST", headers: { Authorization: `Bearer ${token}` } });
    await load();
  };

  const loadPreferences = async () => {
    if (!token) return;
    setPreferencesLoading(true);
    try {
      const response = await fetch("/api/notification-preferences", { headers: { Authorization: `Bearer ${token}` } });
      if (response.ok) setPreferences(await response.json());
    } finally {
      setPreferencesLoading(false);
    }
  };

  const openPreferences = async () => {
    setPreferencesOpen(true);
    await loadPreferences();
  };
  const preferenceFor = (category: string) => preferences.find((preference) => preference.category === category) ?? { category, internal_enabled: true, email_enabled: false };
  const savePreference = async (category: string, field: "internal_enabled" | "email_enabled", value: boolean) => {
    if (!token) return;
    const response = await fetch("/api/notification-preferences", {
      method: "PUT",
      headers: { Authorization: `Bearer ${token}`, "Content-Type": "application/json" },
      body: JSON.stringify({ ...preferenceFor(category), [field]: value }),
    });
    if (response.ok) {
      const saved = await response.json();
      setPreferences((previous) => [...previous.filter((item) => item.category !== category), saved]);
    }
  };

  return <div className="relative">
    <button aria-label="Notificações" onClick={() => setOpen(!open)} className="relative rounded-full p-2 hover:bg-white/10"><Bell size={20} />{count > 0 && <span className="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] text-white">{count > 99 ? "99+" : count}</span>}</button>
    {open && <div className="fixed right-3 top-16 z-50 w-[min(20rem,calc(100vw-1.5rem))] rounded-xl border bg-white p-2 text-slate-700 shadow-xl sm:absolute sm:right-0 sm:top-auto sm:mt-2 sm:w-80">
      <div className="flex items-center justify-between gap-2 px-2 py-1"><strong>Notificações</strong><div className="flex items-center gap-2"><button aria-label="Preferências de notificações" title="Preferências" className="p-1 text-emerald-700" onClick={openPreferences}><Settings2 size={16} /></button><button className="text-xs text-emerald-700 disabled:text-slate-400" disabled={count === 0} onClick={async () => { await fetch("/api/notifications/read-all", { method: "POST", headers: { Authorization: `Bearer ${token}` } }); await load(); }}>Marcar todas</button></div></div>
      {items.length === 0 ? <p className="p-3 text-sm text-gray-400">Nenhuma notificação.</p> : items.map((item) => <div key={item.id} className={`rounded-lg p-2 text-sm ${item.read_at ? "" : "bg-emerald-50"}`}><button className="w-full text-left" onClick={() => markRead(item.id)}><p className="font-semibold">{item.title}</p><p className="text-xs text-gray-500">{item.body}</p></button>{item.link && <Link href={item.link} onClick={() => markRead(item.id)} className="text-xs text-emerald-700">Abrir requerimento</Link>}</div>)}
    </div>}
    {preferencesOpen && <div role="dialog" aria-modal="true" aria-label="Preferências de notificações" className="fixed inset-0 z-[70] flex items-center justify-center bg-slate-950/40 p-4"><section className="w-full max-w-md rounded-2xl bg-white p-5 text-slate-700 shadow-2xl"><div className="mb-4 flex items-center justify-between"><div><h2 className="font-bold">Preferências de notificações</h2><p className="text-xs text-slate-500">Escolha os avisos que deseja receber.</p></div><button aria-label="Fechar preferências" onClick={() => setPreferencesOpen(false)} className="p-1"><X size={20} /></button></div>{preferencesLoading ? <p className="py-4 text-sm text-slate-500">Carregando preferências…</p> : <div className="space-y-3">{Object.entries(preferenceLabels).map(([category, label]) => { const preference = preferenceFor(category); return <div key={category} className="rounded-xl border p-3"><p className="mb-2 text-sm font-medium">{label}</p><label className="flex items-center justify-between gap-3 text-sm"><span>Exibir no sino</span><input aria-label={`${label}: exibir no sino`} type="checkbox" checked={preference.internal_enabled} onChange={(event) => savePreference(category, "internal_enabled", event.target.checked)} /></label><label className="mt-2 flex items-center justify-between gap-3 text-sm"><span>Receber por e-mail</span><input aria-label={`${label}: receber por e-mail`} type="checkbox" checked={preference.email_enabled} onChange={(event) => savePreference(category, "email_enabled", event.target.checked)} /></label></div>; })}</div>}<button onClick={() => setPreferencesOpen(false)} className="mt-5 flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-700 py-2 text-sm font-semibold text-white"><CheckCheck size={16} />Concluir</button></section></div>}
  </div>;
}
