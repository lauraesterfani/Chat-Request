"use client";

import { FormEvent, useCallback, useEffect, useMemo, useState } from "react";
import axios from "axios";
import Link from "next/link";
import { ArrowLeft, Edit3, FileText, Loader2, MessageSquareText, Plus, Power, Search, X } from "lucide-react";

const API_BASE = "/api";

type RequestType = { id: string; name: string };
type ResponseTemplate = {
  id: string;
  title: string;
  content: string;
  is_active: boolean;
  type_requests: RequestType[];
};

const emptyForm = { title: "", content: "", type_request_ids: [] as string[], is_active: true };
const authHeaders = () => ({ Authorization: `Bearer ${sessionStorage.getItem("jwt_token")}` });

export default function ResponseTemplatesPage() {
  const [templates, setTemplates] = useState<ResponseTemplate[]>([]);
  const [types, setTypes] = useState<RequestType[]>([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [isAuthorized, setIsAuthorized] = useState(false);
  const [modalOpen, setModalOpen] = useState(false);
  const [editing, setEditing] = useState<ResponseTemplate | null>(null);
  const [confirming, setConfirming] = useState<ResponseTemplate | null>(null);
  const [form, setForm] = useState(emptyForm);
  const [search, setSearch] = useState("");
  const [status, setStatus] = useState<"all" | "active" | "inactive">("all");
  const [typeFilter, setTypeFilter] = useState("all");
  const [feedback, setFeedback] = useState<{ text: string; error?: boolean } | null>(null);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);

  const loadData = useCallback(async (pageNumber = 1) => {
    try {
      const params = new URLSearchParams({ page: String(pageNumber), per_page: "10" });
      if (search.trim()) params.set("search", search.trim());
      if (status !== "all") params.set("status", status);
      if (typeFilter !== "all") params.set("type_request_id", typeFilter);
      const [templatesResponse, typesResponse] = await Promise.all([
        axios.get<{ data: ResponseTemplate[]; current_page: number; last_page: number }>(`${API_BASE}/response-templates?${params}`, { headers: authHeaders() }),
        axios.get<RequestType[]>(`${API_BASE}/type-requests`, { headers: authHeaders() }),
      ]);
      setTemplates(templatesResponse.data.data || []);
      setPage(templatesResponse.data.current_page || pageNumber);
      setLastPage(templatesResponse.data.last_page || 1);
      setTypes(Array.isArray(typesResponse.data) ? typesResponse.data : []);
    } catch {
      setFeedback({ text: "Não foi possível carregar as respostas pré-configuradas.", error: true });
    } finally {
      setLoading(false);
    }
  }, [search, status, typeFilter]);

  useEffect(() => {
    const initialize = async () => {
      try {
        const me = await axios.get(`${API_BASE}/me`, { headers: authHeaders() });
        const authorized = ["admin", "cradt", "staff"].includes(me.data.role);
        setIsAuthorized(authorized);
        if (authorized) await loadData();
        else setLoading(false);
      } catch {
        setFeedback({ text: "Sua sessão não pôde ser validada.", error: true });
        setLoading(false);
      }
    };
    initialize();
  }, [loadData]);

  const filteredTemplates = useMemo(() => templates, [templates]);

  const showFeedback = (text: string, error = false) => {
    setFeedback({ text, error });
    window.setTimeout(() => setFeedback(null), 4500);
  };

  const openCreate = () => {
    setEditing(null);
    setForm(emptyForm);
    setModalOpen(true);
  };

  const openEdit = (template: ResponseTemplate) => {
    setEditing(template);
    setForm({
      title: template.title,
      content: template.content,
      type_request_ids: template.type_requests.map((type) => type.id),
      is_active: template.is_active,
    });
    setModalOpen(true);
  };

  const toggleType = (id: string) => {
    setForm((current) => ({
      ...current,
      type_request_ids: current.type_request_ids.includes(id)
        ? current.type_request_ids.filter((typeId) => typeId !== id)
        : [...current.type_request_ids, id],
    }));
  };

  const save = async (event: FormEvent) => {
    event.preventDefault();
    if (form.type_request_ids.length === 0) {
      showFeedback("Selecione pelo menos um tipo de requerimento.", true);
      return;
    }

    setSaving(true);
    try {
      const response = editing
        ? await axios.put<ResponseTemplate>(`${API_BASE}/response-templates/${editing.id}`, form, { headers: authHeaders() })
        : await axios.post<ResponseTemplate>(`${API_BASE}/response-templates`, form, { headers: authHeaders() });

      setTemplates((current) => editing
        ? current.map((template) => template.id === editing.id ? response.data : template)
        : [...current, response.data].sort((first, second) => first.title.localeCompare(second.title, "pt-BR")));
      setModalOpen(false);
      showFeedback(editing ? "Resposta pré-configurada atualizada." : "Resposta pré-configurada criada.");
    } catch {
      showFeedback("Não foi possível salvar a resposta pré-configurada.", true);
    } finally {
      setSaving(false);
    }
  };

  const changeStatus = async () => {
    if (!confirming) return;
    const nextStatus = !confirming.is_active;
    setSaving(true);
    try {
      const response = await axios.patch<ResponseTemplate>(
        `${API_BASE}/response-templates/${confirming.id}/status`,
        { is_active: nextStatus },
        { headers: authHeaders() },
      );
      setTemplates((current) => current.map((template) => template.id === confirming.id ? response.data : template));
      showFeedback(nextStatus ? "Resposta ativada." : "Resposta desativada.");
      setConfirming(null);
    } catch {
      showFeedback("Não foi possível alterar o status da resposta.", true);
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return <div className="min-h-screen flex flex-col items-center justify-center gap-3 text-emerald-700"><Loader2 className="animate-spin" size={36} /><p className="font-medium">Carregando respostas...</p></div>;
  }

  if (!isAuthorized) {
    return (
      <main className="min-h-screen flex items-center justify-center bg-slate-50 p-6">
        <section className="max-w-md w-full bg-white rounded-3xl border border-slate-100 shadow-sm p-8 text-center">
          <MessageSquareText className="mx-auto text-slate-400 mb-4" size={42} />
          <h1 className="text-xl font-bold text-slate-800">Acesso restrito</h1>
          <p className="text-sm text-slate-500 mt-2">Este painel é destinado à equipe autorizada para administrar respostas.</p>
          <Link href="/dashboard/admin" className="inline-flex mt-6 px-4 py-2 rounded-xl bg-emerald-700 text-white font-bold text-sm">Voltar ao painel</Link>
        </section>
      </main>
    );
  }

  return (
    <main className="min-h-screen bg-[#F4F6F8] p-4 sm:p-8">
      {feedback && (
        <div role="status" className={`fixed z-[70] top-5 left-1/2 -translate-x-1/2 px-5 py-3 rounded-2xl text-sm font-semibold shadow-xl ${feedback.error ? "bg-red-600 text-white" : "bg-emerald-600 text-white"}`}>
          {feedback.text}
        </div>
      )}

      <div className="max-w-6xl mx-auto">
        <Link href="/dashboard/admin" className="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-emerald-700 mb-6"><ArrowLeft size={17} /> Voltar ao painel</Link>
        <div className="flex flex-col sm:flex-row sm:items-start justify-between gap-5 mb-7">
          <div>
            <h1 className="text-3xl font-bold tracking-tight text-slate-800 flex items-center gap-3"><MessageSquareText className="text-emerald-700" /> Respostas pré-configuradas</h1>
            <p className="text-sm text-slate-500 mt-2">Crie textos-base para padronizar o atendimento aos discentes.</p>
          </div>
          <button onClick={openCreate} className="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-emerald-700 text-white font-bold shadow-sm hover:bg-emerald-800"><Plus size={19} /> Nova resposta</button>
        </div>

        <section className="bg-white rounded-3xl border border-slate-100 shadow-sm p-4 sm:p-5 mb-6">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
            <label className="relative"><span className="sr-only">Pesquisar respostas</span><Search className="absolute left-3 top-3 text-slate-400" size={18} /><input value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} placeholder="Pesquisar por título ou conteúdo" className="w-full pl-10 pr-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 outline-none focus:ring-2 focus:ring-emerald-400" /></label>
            <label><span className="sr-only">Filtrar por status</span><select value={status} onChange={(event) => { setStatus(event.target.value as typeof status); setPage(1); }} className="w-full py-2.5 px-3 rounded-xl bg-slate-50 border border-slate-200 outline-none focus:ring-2 focus:ring-emerald-400"><option value="all">Todos os status</option><option value="active">Ativos</option><option value="inactive">Inativos</option></select></label>
            <label><span className="sr-only">Filtrar por tipo de requerimento</span><select value={typeFilter} onChange={(event) => { setTypeFilter(event.target.value); setPage(1); }} className="w-full py-2.5 px-3 rounded-xl bg-slate-50 border border-slate-200 outline-none focus:ring-2 focus:ring-emerald-400"><option value="all">Todos os tipos</option>{types.map((type) => <option key={type.id} value={type.id}>{type.name}</option>)}</select></label>
          </div>
        </section>

        {lastPage > 1 && <nav className="flex items-center justify-between my-4" aria-label="Paginação de respostas">
          <button disabled={page <= 1} onClick={() => loadData(page - 1)} className="px-4 py-2 rounded-xl bg-white border border-slate-200 text-sm font-bold disabled:opacity-40">Anterior</button>
          <span className="text-sm text-slate-500">Página {page} de {lastPage}</span>
          <button disabled={page >= lastPage} onClick={() => loadData(page + 1)} className="px-4 py-2 rounded-xl bg-white border border-slate-200 text-sm font-bold disabled:opacity-40">Próxima</button>
        </nav>}

        <section className="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
          {filteredTemplates.length === 0 ? (
            <div className="py-20 px-6 text-center"><FileText className="mx-auto text-slate-300" size={44} /><h2 className="font-bold text-slate-700 mt-4">Nenhuma resposta encontrada</h2><p className="text-sm text-slate-500 mt-1">Ajuste os filtros ou cadastre a primeira resposta pré-configurada.</p></div>
          ) : (
            <div className="divide-y divide-slate-100">
              {filteredTemplates.map((template) => (
                <article key={template.id} className="p-5 sm:p-6 flex flex-col lg:flex-row lg:items-start gap-5">
                  <div className="flex-1 min-w-0">
                    <div className="flex flex-wrap items-center gap-2"><h2 className="font-bold text-slate-800 text-lg">{template.title}</h2><span className={`px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wide ${template.is_active ? "bg-emerald-50 text-emerald-700" : "bg-slate-100 text-slate-500"}`}>{template.is_active ? "Ativa" : "Inativa"}</span></div>
                    <p className="mt-2 text-sm leading-relaxed text-slate-600 whitespace-pre-line line-clamp-3">{template.content}</p>
                    <div className="flex flex-wrap gap-2 mt-4">{template.type_requests.map((type) => <span key={type.id} className="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-800 text-xs font-semibold">{type.name}</span>)}</div>
                  </div>
                  <div className="flex gap-2 shrink-0">
                    <button onClick={() => openEdit(template)} aria-label={`Editar ${template.title}`} className="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-slate-200 text-slate-700 text-sm font-bold hover:bg-slate-50"><Edit3 size={16} /> Editar</button>
                    <button onClick={() => setConfirming(template)} aria-label={template.is_active ? `Desativar ${template.title}` : `Ativar ${template.title}`} className={`inline-flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-bold ${template.is_active ? "bg-amber-50 text-amber-800 hover:bg-amber-100" : "bg-emerald-50 text-emerald-700 hover:bg-emerald-100"}`}><Power size={16} /> {template.is_active ? "Desativar" : "Ativar"}</button>
                  </div>
                </article>
              ))}
            </div>
          )}
        </section>
      </div>

      {modalOpen && (
        <div className="fixed inset-0 z-50 bg-black/45 backdrop-blur-sm p-4 flex items-center justify-center">
          <section role="dialog" aria-modal="true" aria-labelledby="template-form-title" className="bg-white w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-3xl shadow-2xl p-6 sm:p-8">
            <div className="flex justify-between gap-4 border-b border-slate-100 pb-5"><div><h2 id="template-form-title" className="text-xl font-bold text-slate-800">{editing ? "Editar resposta" : "Nova resposta"}</h2><p className="text-sm text-slate-500 mt-1">O texto será inserido no atendimento somente após a seleção.</p></div><button onClick={() => setModalOpen(false)} aria-label="Fechar formulário" className="p-2 h-fit rounded-lg text-slate-400 hover:bg-slate-100"><X size={20} /></button></div>
            <form onSubmit={save} className="space-y-5 mt-6">
              <label className="block text-sm font-bold text-slate-700">Título<input required maxLength={120} value={form.title} onChange={(event) => setForm({ ...form, title: event.target.value })} className="mt-1.5 w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 outline-none focus:ring-2 focus:ring-emerald-400 font-normal" placeholder="Ex.: Documentação incompleta" /></label>
              <label className="block text-sm font-bold text-slate-700">Conteúdo da mensagem<textarea required maxLength={5000} value={form.content} onChange={(event) => setForm({ ...form, content: event.target.value })} className="mt-1.5 w-full min-h-40 px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 outline-none focus:ring-2 focus:ring-emerald-400 font-normal resize-y" placeholder="Escreva o texto-base que o atendente poderá revisar antes de enviar." /></label>
              <fieldset><legend className="text-sm font-bold text-slate-700 mb-2">Tipos de requerimento <span className="text-red-600">*</span></legend><div className="grid sm:grid-cols-2 gap-2 max-h-52 overflow-y-auto p-3 rounded-xl border border-slate-200">{types.map((type) => <label key={type.id} className="flex items-center gap-3 p-2 rounded-lg hover:bg-slate-50 cursor-pointer text-sm text-slate-700"><input type="checkbox" checked={form.type_request_ids.includes(type.id)} onChange={() => toggleType(type.id)} className="w-4 h-4 accent-emerald-700" />{type.name}</label>)}{types.length === 0 && <p className="text-sm text-slate-500">Não há tipos de requerimento cadastrados.</p>}</div></fieldset>
              <label className="flex items-center gap-3 text-sm font-bold text-slate-700 cursor-pointer"><input type="checkbox" checked={form.is_active} onChange={(event) => setForm({ ...form, is_active: event.target.checked })} className="w-4 h-4 accent-emerald-700" />Disponibilizar este texto para atendimento</label>
              <div className="flex flex-col-reverse sm:flex-row justify-end gap-3 pt-2"><button type="button" onClick={() => setModalOpen(false)} className="px-5 py-3 rounded-xl text-sm font-bold text-slate-600 bg-slate-100 hover:bg-slate-200">Cancelar</button><button disabled={saving} type="submit" className="px-5 py-3 rounded-xl text-sm font-bold text-white bg-emerald-700 hover:bg-emerald-800 disabled:opacity-60">{saving ? "Salvando..." : "Salvar resposta"}</button></div>
            </form>
          </section>
        </div>
      )}

      {confirming && (
        <div className="fixed inset-0 z-[60] bg-black/45 backdrop-blur-sm p-4 flex items-center justify-center">
          <section role="dialog" aria-modal="true" aria-labelledby="status-confirm-title" className="bg-white w-full max-w-md rounded-3xl shadow-2xl p-7"><h2 id="status-confirm-title" className="text-xl font-bold text-slate-800">{confirming.is_active ? "Desativar resposta?" : "Ativar resposta?"}</h2><p className="text-sm text-slate-600 mt-3">{confirming.is_active ? "Ela deixará de aparecer para os atendentes, mas continuará salva e poderá ser reativada." : "Ela voltará a aparecer apenas nos tipos de requerimento vinculados."}</p><div className="flex justify-end gap-3 mt-7"><button onClick={() => setConfirming(null)} className="px-4 py-2.5 rounded-xl bg-slate-100 text-slate-600 text-sm font-bold">Cancelar</button><button onClick={changeStatus} disabled={saving} className={`px-4 py-2.5 rounded-xl text-white text-sm font-bold disabled:opacity-60 ${confirming.is_active ? "bg-amber-600" : "bg-emerald-700"}`}>{saving ? "Alterando..." : "Confirmar"}</button></div></section>
        </div>
      )}
    </main>
  );
}
