"use client";

import { FormEvent, useCallback, useEffect, useState } from "react";
import Link from "next/link";
import { useAuth } from "../../../context/AuthContext";

type PrivacyRequestType = "access" | "correction" | "erasure";
type PrivacyRequestStatus = "submitted" | "in_review" | "completed" | "rejected";

interface PrivacyDataRequest {
  id: string;
  protocol: string;
  request_type: PrivacyRequestType;
  description: string;
  status: PrivacyRequestStatus;
  response_summary: string | null;
  rejection_reason: string | null;
  responded_at: string | null;
  created_at: string;
}

interface PaginatedRequests {
  data: PrivacyDataRequest[];
  current_page: number;
  last_page: number;
  total: number;
}

const requestTypes: Record<PrivacyRequestType, string> = {
  access: "Acesso aos meus dados",
  correction: "Correção de dados cadastrais",
  erasure: "Avaliação de pedido de eliminação",
};

const statuses: Record<PrivacyRequestStatus, string> = {
  submitted: "Recebida",
  in_review: "Em análise humana",
  completed: "Respondida",
  rejected: "Não atendida",
};

async function responseError(response: Response): Promise<string> {
  const data = await response.json().catch(() => ({}));
  const errors = data.errors ? Object.values(data.errors).flat().join(" ") : "";
  return data.message || errors || "Não foi possível concluir a solicitação. Tente novamente.";
}

export default function PrivacyRequestsPage() {
  const { token } = useAuth();
  const [requests, setRequests] = useState<PrivacyDataRequest[]>([]);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [requestType, setRequestType] = useState<PrivacyRequestType>("access");
  const [description, setDescription] = useState("");
  const [error, setError] = useState("");
  const [notice, setNotice] = useState("");

  const loadRequests = useCallback(async () => {
    if (!token) return;
    setLoading(true);
    setError("");
    try {
      const response = await fetch("/api/privacy/data-requests", {
        headers: { Authorization: `Bearer ${token}`, Accept: "application/json" },
        cache: "no-store",
      });
      if (!response.ok) throw new Error(await responseError(response));
      const result = (await response.json()) as PaginatedRequests;
      setRequests(result.data);
    } catch (loadError) {
      setError(loadError instanceof Error ? loadError.message : "Não foi possível carregar suas solicitações.");
    } finally {
      setLoading(false);
    }
  }, [token]);

  useEffect(() => { void loadRequests(); }, [loadRequests]);

  async function submitRequest(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (!token || submitting) return;
    setSubmitting(true);
    setError("");
    setNotice("");
    try {
      const response = await fetch("/api/privacy/data-requests", {
        method: "POST",
        headers: { Authorization: `Bearer ${token}`, Accept: "application/json", "Content-Type": "application/json" },
        body: JSON.stringify({ request_type: requestType, description }),
      });
      if (!response.ok) throw new Error(await responseError(response));
      const created = (await response.json()) as PrivacyDataRequest;
      setDescription("");
      setNotice(`Solicitação registrada. Guarde o protocolo ${created.protocol}. A equipe fará uma análise humana.`);
      await loadRequests();
    } catch (submitError) {
      setError(submitError instanceof Error ? submitError.message : "Não foi possível registrar a solicitação.");
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div className="mx-auto max-w-4xl space-y-6 py-4 sm:py-8">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <p className="text-xs font-bold uppercase tracking-[0.16em] text-emerald-800">Privacidade</p>
          <h1 className="mt-1 text-2xl font-bold text-slate-900 sm:text-3xl">Solicitações sobre seus dados</h1>
        </div>
        <Link href="/me" className="inline-flex min-h-11 items-center rounded-xl border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700">
          Voltar ao início
        </Link>
      </div>

      <section aria-labelledby="privacy-notice-title" className="rounded-2xl border border-amber-300 bg-amber-50 p-4 sm:p-5">
        <h2 id="privacy-notice-title" className="font-semibold text-amber-950">Informação institucional pendente de aprovação</h2>
        <p className="mt-2 text-sm leading-6 text-amber-950">
          O aviso institucional de privacidade e o canal oficial para esse tipo de pedido ainda precisam ser confirmados. Esta tela permite registrar uma solicitação no Chat Request para análise humana; o protocolo não confirma prazo legal, exportação, correção de histórico nem exclusão automática de dados.
        </p>
        <Link href="/privacidade" className="mt-3 inline-flex min-h-11 items-center font-semibold text-amber-950 underline underline-offset-4 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800">Consultar o estado do aviso de privacidade</Link>
      </section>

      <section aria-labelledby="new-privacy-request-title" className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
        <h2 id="new-privacy-request-title" className="text-lg font-bold text-slate-900">Abrir uma solicitação</h2>
        <p id="privacy-request-guidance" className="mt-1 text-sm leading-6 text-slate-600">
          Descreva apenas o necessário. Não inclua senhas, documentos, dados médicos ou informações de outras pessoas. Não é possível anexar arquivos por este formulário.
        </p>

        <form className="mt-5 space-y-4" onSubmit={submitRequest}>
          <div>
            <label htmlFor="privacy-request-type" className="block text-sm font-semibold text-slate-800">Tipo de solicitação</label>
            <select id="privacy-request-type" value={requestType} onChange={(event) => setRequestType(event.target.value as PrivacyRequestType)} className="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700">
              {Object.entries(requestTypes).map(([value, label]) => <option key={value} value={value}>{label}</option>)}
            </select>
          </div>
          <div>
            <label htmlFor="privacy-request-description" className="block text-sm font-semibold text-slate-800">Como podemos analisar seu pedido?</label>
            <textarea id="privacy-request-description" value={description} onChange={(event) => setDescription(event.target.value)} maxLength={1000} minLength={10} required aria-describedby="privacy-request-guidance privacy-request-count" rows={5} className="mt-1 w-full resize-y rounded-xl border border-slate-300 bg-white px-3 py-3 text-sm leading-6 text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700" placeholder="Explique de forma breve o que você precisa." />
            <p id="privacy-request-count" className="mt-1 text-right text-xs text-slate-500">{description.length}/1000 caracteres</p>
          </div>
          {error && <p role="alert" className="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800">{error}</p>}
          {notice && <p role="status" className="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm leading-6 text-emerald-900">{notice}</p>}
          <button type="submit" disabled={submitting || description.trim().length < 10} className="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-[#006b55] px-5 py-3 text-sm font-bold text-white transition-colors hover:bg-[#005743] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800 disabled:cursor-not-allowed disabled:bg-slate-400 sm:w-auto">
            {submitting ? "Registrando…" : "Registrar solicitação"}
          </button>
        </form>
      </section>

      <section aria-labelledby="privacy-history-title" className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
        <div className="flex flex-wrap items-end justify-between gap-2">
          <div>
            <h2 id="privacy-history-title" className="text-lg font-bold text-slate-900">Seu histórico</h2>
            <p className="mt-1 text-sm text-slate-600">Somente você pode consultar os pedidos registrados na sua conta.</p>
          </div>
          <button type="button" onClick={() => void loadRequests()} disabled={loading} className="min-h-11 rounded-xl px-3 text-sm font-semibold text-emerald-800 hover:bg-emerald-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700 disabled:opacity-60">Atualizar</button>
        </div>

        {loading ? <p role="status" className="mt-5 text-sm text-slate-600">Carregando seu histórico…</p> : requests.length === 0 ? <p className="mt-5 rounded-xl bg-slate-50 p-4 text-sm text-slate-600">Você ainda não registrou solicitações de privacidade.</p> : (
          <ul className="mt-5 space-y-3">
            {requests.map((item) => (
              <li key={item.id} className="rounded-xl border border-slate-200 p-4 sm:p-5">
                <div className="flex flex-wrap items-start justify-between gap-3">
                  <div>
                    <h3 className="font-semibold text-slate-900">{requestTypes[item.request_type]}</h3>
                    <p className="mt-1 break-all font-mono text-xs text-slate-600">Protocolo {item.protocol}</p>
                  </div>
                  <span className="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-900">{statuses[item.status]}</span>
                </div>
                <p className="mt-3 whitespace-pre-wrap break-words text-sm leading-6 text-slate-700">{item.description}</p>
                {item.response_summary && <div className="mt-4 rounded-lg bg-slate-50 p-3"><h4 className="text-sm font-semibold text-slate-900">Resposta da análise</h4><p className="mt-1 whitespace-pre-wrap text-sm leading-6 text-slate-700">{item.response_summary}</p></div>}
                {item.rejection_reason && <div className="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3"><h4 className="text-sm font-semibold text-amber-950">Motivo informado</h4><p className="mt-1 whitespace-pre-wrap text-sm leading-6 text-amber-950">{item.rejection_reason}</p></div>}
                <p className="mt-3 text-xs text-slate-500">Registrada em {new Date(item.created_at).toLocaleString("pt-BR")}{item.responded_at ? ` · Respondida em ${new Date(item.responded_at).toLocaleString("pt-BR")}` : ""}</p>
              </li>
            ))}
          </ul>
        )}
      </section>
    </div>
  );
}
