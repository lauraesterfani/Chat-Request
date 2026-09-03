"use client";

import { useEffect, useState } from "react";
import Link from "next/link";

type Item = { id: string; protocol?: string; subject: string; status: string; responsible_sector?: string; priority?: string; assigned_staff?: { name: string } | null; sla_indicator?: string };

export default function QueuePage() {
  const [items, setItems] = useState<Item[]>([]); const [loading, setLoading] = useState(true);
  useEffect(() => { const token = sessionStorage.getItem("jwt_token"); fetch("/api/admin/queue?per_page=50", { headers: { Authorization: `Bearer ${token}` } }).then(r => r.ok ? r.json() : { data: [] }).then(d => setItems(d.data ?? [])).finally(() => setLoading(false)); }, []);
  const label: Record<string,string> = { no_policy: "Prazo não definido", on_time: "Dentro do prazo", near_due: "Próximo do vencimento", overdue: "Atrasado" };
  return <main className="min-h-screen bg-[#F4F6F8] p-6 md:p-10"><div className="max-w-6xl mx-auto"><div className="flex justify-between items-center mb-8"><div><h1 className="text-3xl font-bold text-slate-800">Fila de atendimentos</h1><p className="text-gray-500">Pedidos abertos do setor autorizado</p></div><Link href="/dashboard/admin" className="text-emerald-700">Voltar ao painel</Link></div>{loading ? <p>Carregando fila...</p> : <div className="bg-white rounded-2xl shadow-sm overflow-hidden"><div className="grid grid-cols-6 gap-3 p-4 text-xs font-bold uppercase text-gray-400 border-b"><span>Protocolo</span><span>Serviço</span><span>Setor</span><span>Atendente</span><span>Prioridade</span><span>SLA</span></div>{items.map(item => <Link key={item.id} href={`/requests/acesso/${item.id}`} className="grid grid-cols-6 gap-3 p-4 border-b hover:bg-emerald-50 text-sm"><span className="font-semibold">{item.protocol ?? "—"}</span><span>{item.subject}</span><span>{item.responsible_sector ?? "—"}</span><span>{item.assigned_staff?.name ?? "Sem atendente"}</span><span>{item.priority ?? "normal"}</span><span className={item.sla_indicator === "overdue" ? "text-red-600 font-bold" : "text-emerald-700"}>{label[item.sla_indicator ?? ""] ?? "—"}</span></Link>)}</div>}</div></main>;
}
