"use client";

import { useEffect, useState } from "react";

type EventItem = { id: string; event_type: string; sector?: string; data?: { from?: string; to?: string; reason?: string; status?: string }; created_at: string };
const labels: Record<string, string> = { created: "Requerimento recebido", status_changed: "Etapa atualizada", forwarded: "Requerimento encaminhado", final_decision: "Decisão registrada" };

export default function RequestTimeline({ requestId }: { requestId: string }) {
  const [events, setEvents] = useState<EventItem[]>([]);
  useEffect(() => {
    const token = sessionStorage.getItem("jwt_token");
    fetch(`/api/requests/${requestId}/events`, { headers: { Authorization: `Bearer ${token}`, Accept: "application/json" } })
      .then((response) => response.ok ? response.json() : [])
      .then(setEvents)
      .catch(() => setEvents([]));
  }, [requestId]);
  if (!events.length) return null;
  return <section className="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm" aria-label="Linha do tempo">
    <h3 className="mb-4 text-xs font-black uppercase tracking-widest text-emerald-800">Linha do tempo</h3>
    <ol className="space-y-3">{events.map((event) => <li key={event.id} className="border-l-2 border-emerald-200 pl-4"><p className="text-sm font-semibold text-slate-700">{labels[event.event_type] || event.event_type}</p><p className="text-xs text-slate-500">{event.data?.to ? `${event.data.from || ""} → ${event.data.to}` : event.sector || ""} · {new Date(event.created_at).toLocaleString("pt-BR")}</p></li>)}</ol>
  </section>;
}
