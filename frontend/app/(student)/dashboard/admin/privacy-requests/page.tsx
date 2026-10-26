"use client";

import { useCallback, useEffect, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";

type RequestStatus = "submitted" | "in_review" | "completed" | "rejected";
type RequestType = "access" | "correction" | "erasure";
interface PrivacyRequest {
  id: string;
  protocol: string;
  request_type: RequestType;
  description: string;
  status: RequestStatus;
  response_summary: string | null;
  rejection_reason: string | null;
  created_at: string;
  responded_at: string | null;
  user: { id: string; name: string; matricula: string };
}
interface PageResult { data: PrivacyRequest[]; current_page: number; last_page: number; total: number }

const typeLabels: Record<RequestType, string> = {
  access: "Acesso aos dados",
  correction: "Correção cadastral",
  erasure: "Avaliação de eliminação",
};
const statusLabels: Record<RequestStatus, string> = {
  submitted: "Recebida",
  in_review: "Em análise humana",
  completed: "Respondida",
  rejected: "Não atendida",
};

async function readError(response: Response) {
  const data = await response.json().catch(() => ({}));
  return data.errors ? Object.values(data.errors).flat().join(" ") : data.message || "Não foi possível concluir a operação.";
}

export default function PrivacyRequestsAdminPage() {
  const router = useRouter();
  const [items, setItems] = useState<PrivacyRequest[]>([]);
  const [selected, setSelected] = useState<PrivacyRequest | null>(null);
  const [status, setStatus] = useState<RequestStatus | "">("submitted");
  const [nextStatus, setNextStatus] = useState<"in_review" | "completed" | "rejected">("in_review");
  const [summary, setSummary] = useState("");
  const [reason, setReason] = useState("");
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState("");
  const [notice, setNotice] = useState("");

  const loadItems = useCallback(async () => {
    setLoading(true);
    setError("");
    const token = sessionStorage.getItem("jwt_token");
    if (!token) { router.replace("/cradt-login"); return; }
    const query = status ? "?status=" + status : "";
    try {
      const response = await fetch("/api/admin/privacy/data-requests" + query, {
        headers: { Accept: "application/json", Authorization: "Bearer " + token }, cache: "no-store",
      });
      if (!response.ok) throw new Error(await readError(response));
      const result = await response.json() as PageResult;
      setItems(result.data);
      if (selected && !result.data.some((item) => item.id === selected.id)) setSelected(null);
    } catch (loadError) {
      setError(loadError instanceof Error ? loadError.message : "Falha ao carregar solicitações.");
    } finally { setLoading(false); }
  }, [router, selected, status]);

  useEffect(() => { void loadItems(); }, [loadItems]);

  function selectRequest(item: PrivacyRequest) {
    setSelected(item);
    setNextStatus(item.status === "submitted" ? "in_review" : "completed");
    setSummary("");
    setReason("");
    setNotice("");
    setError("");
  }

  async function processRequest(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (!selected || saving) return;
    const token = sessionStorage.getItem("jwt_token");
    if (!token) { router.replace("/cradt-login"); return; }
    setSaving(true); setError(""); setNotice("");
    try {
      const payload: Record<string, string> = { status: nextStatus };
      if (nextStatus === "completed" || nextStatus === "rejected") payload.response_summary = summary;
      if (nextStatus === "rejected") payload.rejection_reason = reason;
      const response = await fetch("/api/admin/privacy/data-requests/" + selected.id, {
        method: "PATCH",
        headers: { Accept: "application/json", Authorization: "Bearer " + token, "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
      if (!response.ok) throw new Error(await readError(response));
      const updated = await response.json() as PrivacyRequest;
      setSelected(updated);
      setStatus(updated.status);
      setNotice("Etapa atualizada e registrada no histórico de atendimento.");
    } catch (saveError) {
      setError(saveError instanceof Error ? saveError.message : "Falha ao atualizar solicitação.");
    } finally { setSaving(false); }
  }

  const canTransition = selected?.status === "submitted" || selected?.status === "in_review";
  const finalAction = nextStatus === "completed" || nextStatus === "rejected";

  return (
    <main className="mx-auto max-w-7xl space-y-6 py-4 sm:py-8">
      <header className="flex flex-wrap items-start justify-between gap-4">
        <div>
          <p className="text-xs font-bold uppercase tracking-[0.16em] text-emerald-800">Privacidade · revisão humana</p>
          <h1 className="mt-1 text-2xl font-bold text-slate-900 sm:text-3xl">Solicitações sobre dados pessoais</h1>
          <p className="mt-2 max-w-3xl text-sm leading-6 text-slate-600">A designação e o canal institucional de privacidade ainda precisam ser confirmados. O acesso administrativo nesta tela não significa que a pessoa seja o encarregado institucional.</p>
        </div>
        <Link href="/dashboard/admin" className="inline-flex min-h-11 items-center rounded-xl border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700">Voltar ao painel</Link>
      </header>

      <div className="grid gap-5 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
        <section aria-labelledby="requests-heading" className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
          <div className="flex flex-wrap items-center justify-between gap-3">
            <h2 id="requests-heading" className="text-lg font-bold text-slate-900">Fila de análise</h2>
            <label className="text-sm font-semibold text-slate-700">Etapa
              <select value={status} onChange={(event) => setStatus(event.target.value as RequestStatus | "")} className="ml-2 min-h-10 rounded-lg border border-slate-300 bg-white px-2 text-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-700">
                <option value="">Todas</option><option value="submitted">Recebida</option><option value="in_review">Em análise</option><option value="completed">Respondida</option><option value="rejected">Não atendida</option>
              </select>
            </label>
          </div>
          {error && <p role="alert" className="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800">{error}</p>}
          {loading ? <p role="status" className="mt-5 text-sm text-slate-600">Carregando solicitações…</p> : items.length === 0 ? <p className="mt-5 rounded-xl bg-slate-50 p-4 text-sm text-slate-600">Não há solicitações nesta etapa.</p> : (
            <ul className="mt-4 space-y-2">
              {items.map((item) => <li key={item.id}>
                <button type="button" onClick={() => selectRequest(item)} aria-pressed={selected?.id === item.id} className={"w-full rounded-xl border p-4 text-left transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700 " + (selected?.id === item.id ? "border-emerald-600 bg-emerald-50" : "border-slate-200 hover:border-emerald-300 hover:bg-slate-50")}>
                  <span className="flex flex-wrap items-start justify-between gap-2"><span className="font-semibold text-slate-900">{item.user.name}</span><span className="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700">{statusLabels[item.status]}</span></span>
                  <span className="mt-1 block text-sm text-slate-700">{typeLabels[item.request_type]}</span>
                  <span className="mt-2 block break-all font-mono text-xs text-slate-500">{item.protocol}</span>
                </button>
              </li>)}
            </ul>
          )}
        </section>

        <section aria-labelledby="detail-heading" className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
          <h2 id="detail-heading" className="text-lg font-bold text-slate-900">Detalhes e decisão de atendimento</h2>
          {!selected ? <p className="mt-5 rounded-xl bg-slate-50 p-4 text-sm text-slate-600">Selecione uma solicitação para consultar os dados e registrar a próxima etapa.</p> : <>
            <div className="mt-4 rounded-xl bg-slate-50 p-4">
              <div className="flex flex-wrap justify-between gap-2"><p className="font-semibold text-slate-900">{selected.user.name} <span className="font-normal text-slate-600">· matrícula {selected.user.matricula}</span></p><span className="text-xs font-bold text-emerald-900">{statusLabels[selected.status]}</span></div>
              <p className="mt-2 text-sm text-slate-700">{typeLabels[selected.request_type]} · <span className="font-mono text-xs">{selected.protocol}</span></p>
              <p className="mt-3 whitespace-pre-wrap break-words text-sm leading-6 text-slate-800">{selected.description}</p>
              <p className="mt-3 text-xs text-slate-500">Recebida em {new Date(selected.created_at).toLocaleString("pt-BR")}</p>
            </div>
            {selected.response_summary && <div className="mt-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4"><p className="text-sm font-semibold text-emerald-950">Resumo da resposta</p><p className="mt-1 whitespace-pre-wrap text-sm leading-6 text-emerald-950">{selected.response_summary}</p>{selected.rejection_reason && <p className="mt-3 text-sm text-amber-950"><strong>Motivo de não atendimento:</strong> {selected.rejection_reason}</p>}</div>}
            {notice && <p role="status" className="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-900">{notice}</p>}
            {canTransition ? <form onSubmit={processRequest} className="mt-5 space-y-4 border-t border-slate-200 pt-5">
              <div><label htmlFor="privacy-next-status" className="block text-sm font-semibold text-slate-800">Próxima etapa</label><select id="privacy-next-status" value={nextStatus} onChange={(event) => setNextStatus(event.target.value as "in_review" | "completed" | "rejected")} className="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-700">
                {selected.status === "submitted" && <option value="in_review">Iniciar análise humana</option>}{selected.status === "in_review" && <><option value="completed">Concluir com resposta</option><option value="rejected">Não atender com justificativa</option></>}
              </select></div>
              {finalAction && <div><label htmlFor="privacy-response-summary" className="block text-sm font-semibold text-slate-800">Resumo privado da resposta ao solicitante</label><textarea id="privacy-response-summary" value={summary} onChange={(event) => setSummary(event.target.value)} maxLength={1200} minLength={1} required rows={4} className="mt-1 w-full rounded-xl border border-slate-300 px-3 py-3 text-sm leading-6 focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-700" placeholder="Registre uma resposta clara para o solicitante." /></div>}
              {nextStatus === "rejected" && <div><label htmlFor="privacy-rejection-reason" className="block text-sm font-semibold text-slate-800">Fundamentação da rejeição</label><textarea id="privacy-rejection-reason" value={reason} onChange={(event) => setReason(event.target.value)} maxLength={800} minLength={1} required rows={3} className="mt-1 w-full rounded-xl border border-amber-300 px-3 py-3 text-sm leading-6 focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-700" placeholder="Explique o motivo da decisão." /></div>}
              <p className="text-xs leading-5 text-slate-500">A análise não exporta dados, não corrige registros históricos e não elimina dados automaticamente. Encaminhe a solicitação conforme os procedimentos institucionais aprovados.</p>
              <button type="submit" disabled={saving || (finalAction && !summary.trim()) || (nextStatus === "rejected" && !reason.trim())} className="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-[#006b55] px-5 py-3 text-sm font-bold text-white hover:bg-[#005743] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800 disabled:cursor-not-allowed disabled:bg-slate-400 sm:w-auto">{saving ? "Salvando…" : "Registrar etapa"}</button>
            </form> : <p className="mt-5 rounded-xl bg-slate-50 p-4 text-sm text-slate-600">Esta solicitação está encerrada e permanece disponível para consulta.</p>}
          </>}
        </section>
      </div>
    </main>
  );
}
