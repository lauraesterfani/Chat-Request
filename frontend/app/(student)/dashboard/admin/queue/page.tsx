"use client";

import { useEffect, useState } from "react";
import Link from "next/link";

type Item = { id: string; protocol?: string; subject: string; status: string; responsible_sector?: string; priority?: string; assigned_staff?: { name: string } | null; sla_indicator?: string };

export default function QueuePage() {
  const [items, setItems] = useState<Item[]>([]); const [loading, setLoading] = useState(true);
  useEffect(() => { const token = sessionStorage.getItem("jwt_token"); fetch("/api/admin/queue?per_page=50", { headers: { Authorization: `Bearer ${token}` } }).then(r => r.ok ? r.json() : { data: [] }).then(d => setItems(d.data ?? [])).finally(() => setLoading(false)); }, []);
  const label: Record<string,string> = { no_policy: "Prazo não definido", on_time: "Dentro do prazo", near_due: "Próximo do vencimento", overdue: "Atrasado" };
  return (
    <main className="min-h-screen bg-[#F4F6F8] p-4 sm:p-6 md:p-10">
      <div className="mx-auto max-w-6xl">
        <div className="mb-6 flex flex-col gap-4 sm:mb-8 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 className="text-3xl font-bold text-slate-800">Fila de atendimentos</h1>
            <p className="text-gray-500">Pedidos abertos do setor autorizado</p>
          </div>
          <Link href="/dashboard/admin" className="self-start text-emerald-700 hover:underline sm:self-auto">Voltar ao painel</Link>
        </div>

        {loading ? <p role="status">Carregando fila...</p> : (
          <section aria-label="Pedidos abertos" className="overflow-hidden rounded-2xl bg-white shadow-sm">
            {items.length === 0 ? (
              <p role="status" className="p-6 text-sm text-slate-600">Não há pedidos abertos no escopo de acesso atual.</p>
            ) : (
              <>
                <div className="hidden grid-cols-6 gap-3 border-b p-4 text-xs font-bold uppercase text-gray-500 md:grid">
                  <span>Protocolo</span><span>Serviço</span><span>Setor</span><span>Atendente</span><span>Prioridade</span><span>SLA</span>
                </div>
                <div className="divide-y divide-slate-100">
                  {items.map((item) => (
                    <Link key={item.id} href={`/requests/acesso/${item.id}`} className="grid grid-cols-1 gap-2 p-4 text-sm hover:bg-emerald-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-700 md:grid-cols-6 md:gap-3">
                      <span className="min-w-0 break-words font-semibold"><span className="mr-2 text-xs font-bold uppercase text-slate-500 md:hidden">Protocolo</span>{item.protocol ?? "—"}</span>
                      <span className="min-w-0 break-words"><span className="mr-2 text-xs font-bold uppercase text-slate-500 md:hidden">Serviço</span>{item.subject}</span>
                      <span className="min-w-0 break-words"><span className="mr-2 text-xs font-bold uppercase text-slate-500 md:hidden">Setor</span>{item.responsible_sector ?? "—"}</span>
                      <span className="min-w-0 break-words"><span className="mr-2 text-xs font-bold uppercase text-slate-500 md:hidden">Atendente</span>{item.assigned_staff?.name ?? "Sem atendente"}</span>
                      <span className="min-w-0 break-words"><span className="mr-2 text-xs font-bold uppercase text-slate-500 md:hidden">Prioridade</span>{item.priority ?? "normal"}</span>
                      <span className={`min-w-0 break-words ${item.sla_indicator === "overdue" ? "font-bold text-red-600" : "text-emerald-700"}`}><span className="mr-2 text-xs font-bold uppercase text-slate-500 md:hidden">SLA</span>{label[item.sla_indicator ?? ""] ?? "—"}</span>
                    </Link>
                  ))}
                </div>
              </>
            )}
          </section>
        )}
      </div>
    </main>
  );
}
