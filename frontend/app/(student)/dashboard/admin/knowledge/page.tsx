"use client";

import { FormEvent, useCallback, useEffect, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { ArrowLeft, BookOpen, ChevronLeft, ChevronRight, FilePlus2, LoaderCircle, Save, Search } from "lucide-react";

type ArticleStatus = "draft" | "review" | "published" | "inactive";
type ArticleAudience = "public" | "authenticated" | "internal";

interface KnowledgeArticleVersion {
  id: string;
  version: number;
  event: string;
  status: ArticleStatus;
  title: string;
  summary: string;
  content: string;
  category: string | null;
  audience: ArticleAudience;
  source_reference: string | null;
  created_by_name: string | null;
  reviewed_by_name: string | null;
  acted_by_name: string | null;
  publication_confirmed: boolean;
  publication_confirmed_at: string | null;
  published_at: string | null;
  created_at: string;
}

interface KnowledgeArticle {
  id: string;
  title: string;
  summary: string;
  content: string;
  category: string | null;
  audience: ArticleAudience;
  source_reference: string | null;
  status: ArticleStatus;
  updated_by: string | null;
  published_at: string | null;
  published_version_id: string | null;
  published_version: KnowledgeArticleVersion | null;
  publication_active: boolean;
  versions: KnowledgeArticleVersion[];
  can_publish: boolean;
  created_at: string;
  updated_at: string;
}

interface ArticlePage {
  data: KnowledgeArticle[];
  current_page: number;
  last_page: number;
  total: number;
}

interface ArticleForm {
  title: string;
  summary: string;
  content: string;
  category: string;
  audience: ArticleAudience;
  source_reference: string;
}

const blankForm: ArticleForm = {
  title: "",
  summary: "",
  content: "",
  category: "",
  audience: "public",
  source_reference: "",
};

const statusLabels: Record<ArticleStatus, string> = {
  draft: "Rascunho",
  review: "Em revisão",
  published: "Publicado",
  inactive: "Desativado",
};

const audienceLabels: Record<ArticleAudience, string> = {
  public: "Público",
  authenticated: "Autenticados",
  internal: "Equipe interna",
};

const versionEventLabels: Record<string, string> = {
  created: "Rascunho criado",
  draft_saved: "Rascunho salvo",
  submitted_for_review: "Enviado para revisão",
  published: "Publicado",
  deactivated: "Desativado",
  legacy_publication_preserved: "Publicação legada preservada; autoria histórica não disponível",
};

function getErrorMessage(data: { message?: string; errors?: Record<string, string[]> }) {
  const firstFieldError = data.errors ? Object.values(data.errors).flat()[0] : null;
  return firstFieldError ?? data.message ?? "Não foi possível salvar a orientação.";
}

export default function KnowledgeArticlesPage() {
  const router = useRouter();
  const [articles, setArticles] = useState<KnowledgeArticle[]>([]);
  const [article, setArticle] = useState<KnowledgeArticle | null>(null);
  const [form, setForm] = useState<ArticleForm>(blankForm);
  const [queryInput, setQueryInput] = useState("");
  const [query, setQuery] = useState("");
  const [statusFilter, setStatusFilter] = useState<ArticleStatus | "">("");
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState("");
  const [notice, setNotice] = useState("");
  const [publicationConfirmed, setPublicationConfirmed] = useState(false);

  const loadArticles = useCallback(async (targetPage = page) => {
    setLoading(true);
    setError("");
    const token = sessionStorage.getItem("jwt_token");
    if (!token) {
      router.replace("/cradt-login");
      return;
    }

    const params = new URLSearchParams({ per_page: "25", page: String(targetPage) });
    if (query) params.set("q", query);
    if (statusFilter) params.set("status", statusFilter);

    try {
      const response = await fetch(`/api/admin/knowledge-articles?${params}`, {
        headers: { Accept: "application/json", Authorization: `Bearer ${token}` },
        cache: "no-store",
      });
      const data = await response.json().catch(() => ({}));
      if (response.status === 401) {
        sessionStorage.removeItem("jwt_token");
        router.replace("/cradt-login");
        return;
      }
      if (!response.ok) throw new Error(data.message ?? "Não foi possível carregar as orientações.");
      const result = data as ArticlePage;
      setArticles(result.data ?? []);
      setPage(result.current_page ?? targetPage);
      setLastPage(result.last_page ?? 1);
      setTotal(result.total ?? 0);
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : "Não foi possível carregar as orientações.");
    } finally {
      setLoading(false);
    }
  }, [page, query, router, statusFilter]);

  useEffect(() => {
    void loadArticles(1);
    // Filter changes intentionally reset the paginated list to its first page.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [query, statusFilter]);

  const startNew = () => {
    setArticle(null);
    setForm(blankForm);
    setPublicationConfirmed(false);
    setError("");
    setNotice("");
  };

  const editArticle = (selected: KnowledgeArticle) => {
    setArticle(selected);
    setPublicationConfirmed(false);
    setForm({
      title: selected.title,
      summary: selected.summary,
      content: selected.content,
      category: selected.category ?? "",
      audience: selected.audience,
      source_reference: selected.source_reference ?? "",
    });
    setError("");
    setNotice("");
  };

  const save = async (status: ArticleStatus) => {
    setError("");
    setNotice("");

    if (status === "published" && !form.source_reference.trim()) {
      setError("Informe a fonte ou referência antes de publicar.");
      return;
    }
    if (status === "published" && !publicationConfirmed) {
      setError("Confirme que revisou o conteúdo e que a publicação está autorizada.");
      return;
    }
    if (status === "published" && !window.confirm("Publicar a versão revisada para o público selecionado?")) return;
    if (status === "review" && !article) {
      setError("Salve primeiro o rascunho para iniciar o fluxo de revisão.");
      return;
    }

    const token = sessionStorage.getItem("jwt_token");
    if (!token) {
      router.replace("/cradt-login");
      return;
    }
    setSaving(true);
    try {
      const response = await fetch(article ? `/api/admin/knowledge-articles/${article.id}` : "/api/admin/knowledge-articles", {
        method: article ? "PUT" : "POST",
        headers: { Accept: "application/json", "Content-Type": "application/json", Authorization: `Bearer ${token}` },
        body: JSON.stringify({
          title: form.title.trim(),
          summary: form.summary.trim(),
          content: form.content,
          category: form.category.trim() || null,
          audience: form.audience,
          source_reference: form.source_reference.trim() || null,
          status,
          ...(status === "published" ? { publication_confirmed: publicationConfirmed } : {}),
        }),
      });
      const data = await response.json().catch(() => ({}));
      if (!response.ok) throw new Error(getErrorMessage(data));
      const saved = data as KnowledgeArticle;
      setArticle(saved);
      setPublicationConfirmed(false);
      setNotice(status === "published" ? "Orientação publicada." : status === "review" ? "Orientação enviada para revisão." : status === "inactive" ? "Orientação desativada." : "Rascunho salvo.");
      await loadArticles(page);
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : "Não foi possível salvar a orientação.");
    } finally {
      setSaving(false);
    }
  };

  const pendingVersion = article?.versions?.at(-1) ?? null;

  const submitSearch = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setQuery(queryInput.trim());
  };

  return (
    <div className="min-h-[calc(100vh-5rem)] bg-slate-50">
      <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <div className="mb-6 flex flex-wrap items-center justify-between gap-4">
          <div className="flex min-w-0 items-center gap-3">
            <Link href="/dashboard/admin" aria-label="Voltar ao painel administrativo" className="grid size-11 shrink-0 place-items-center rounded-xl border border-slate-200 bg-white text-slate-700 transition hover:border-emerald-700 hover:text-emerald-800 focus-visible:outline-emerald-700">
              <ArrowLeft size={19} aria-hidden="true" />
            </Link>
            <div className="flex min-w-0 items-center gap-3">
              <span className="grid size-11 shrink-0 place-items-center rounded-xl bg-[#004d40] text-white"><BookOpen size={20} aria-hidden="true" /></span>
              <div className="min-w-0">
                <p className="text-xs font-semibold uppercase tracking-[0.14em] text-emerald-800">Conteúdo institucional</p>
                <h1 className="truncate text-xl font-bold text-slate-950 sm:text-2xl">Base de conhecimento</h1>
              </div>
            </div>
          </div>
          <button type="button" onClick={startNew} className="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-[#004d40] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-900 focus-visible:outline-emerald-700">
            <FilePlus2 size={17} aria-hidden="true" /> Nova orientação
          </button>
        </div>

        <div className="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm leading-6 text-emerald-950">
          Rascunhos e itens em revisão não aparecem na busca pública. A publicação exige fonte ou referência e uma confirmação explícita.
        </div>

        {error && <p role="alert" className="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">{error}</p>}
        {notice && <p role="status" className="mb-4 rounded-xl border border-emerald-200 bg-white px-4 py-3 text-sm font-medium text-emerald-900">{notice}</p>}

        <div className="grid items-start gap-5 lg:grid-cols-[minmax(280px,0.82fr)_minmax(0,1.5fr)]">
          <section aria-labelledby="articles-heading" className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div className="border-b border-slate-200 p-4 sm:p-5">
              <div className="mb-4 flex items-baseline justify-between gap-3">
                <h2 id="articles-heading" className="font-bold text-slate-900">Orientações cadastradas</h2>
                <span className="text-xs text-slate-500">{total} {total === 1 ? "item" : "itens"}</span>
              </div>
              <form onSubmit={submitSearch} className="grid gap-3">
                <label className="relative block">
                  <span className="sr-only">Buscar por título ou resumo</span>
                  <Search size={17} aria-hidden="true" className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-500" />
                  <input value={queryInput} onChange={(event) => setQueryInput(event.target.value)} placeholder="Buscar orientação" className="min-h-11 w-full rounded-lg border border-slate-300 bg-white pl-9 pr-3 text-sm text-slate-900 placeholder:text-slate-500 focus-visible:outline-emerald-700" />
                </label>
                <div className="flex gap-2">
                  <label className="sr-only" htmlFor="article-status-filter">Filtrar por situação</label>
                  <select id="article-status-filter" value={statusFilter} onChange={(event) => setStatusFilter(event.target.value as ArticleStatus | "")} className="min-h-11 min-w-0 flex-1 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 focus-visible:outline-emerald-700">
                    <option value="">Todas as situações</option>
                    {Object.entries(statusLabels).map(([value, label]) => <option key={value} value={value}>{label}</option>)}
                  </select>
                  <button type="submit" className="min-h-11 rounded-lg border border-emerald-800 px-4 text-sm font-semibold text-emerald-900 transition hover:bg-emerald-50 focus-visible:outline-emerald-700">Buscar</button>
                </div>
              </form>
            </div>

            {loading ? (
              <div role="status" className="flex min-h-40 items-center justify-center gap-2 text-sm text-slate-600"><LoaderCircle size={18} className="animate-spin" aria-hidden="true" />Carregando orientações…</div>
            ) : articles.length === 0 ? (
              <div className="px-5 py-10 text-center">
                <p className="font-semibold text-slate-800">Nenhuma orientação encontrada</p>
                <p className="mt-1 text-sm leading-6 text-slate-600">Crie um rascunho fictício para iniciar a revisão.</p>
              </div>
            ) : (
              <ul className="divide-y divide-slate-100">
                {articles.map((item) => (
                  <li key={item.id}>
                    <button type="button" aria-pressed={article?.id === item.id} onClick={() => editArticle(item)} className={`block min-h-20 w-full px-4 py-4 text-left transition hover:bg-emerald-50/60 focus-visible:outline-emerald-700 sm:px-5 ${article?.id === item.id ? "border-l-4 border-emerald-700 bg-emerald-50/70" : "border-l-4 border-transparent"}`}>
                      <span className="flex flex-wrap items-center gap-2">
                        <span className="font-semibold text-slate-900">{item.title}</span>
                        <span className={`rounded-full px-2 py-0.5 text-xs font-semibold ${item.status === "published" ? "bg-emerald-100 text-emerald-900" : item.status === "review" ? "bg-amber-100 text-amber-950" : item.status === "inactive" ? "bg-slate-200 text-slate-700" : "bg-blue-50 text-blue-900"}`}>{statusLabels[item.status]}</span>
                        {item.published_version && item.status !== "published" && <span className={`rounded-full px-2 py-0.5 text-xs font-semibold ${item.publication_active ? "bg-emerald-50 text-emerald-900" : "bg-slate-100 text-slate-700"}`}>{item.publication_active ? `Publicada v${item.published_version.version}` : "Sem publicação ativa"}</span>}
                      </span>
                      <span className="mt-1 block line-clamp-2 text-sm leading-5 text-slate-600">{item.summary}</span>
                      <span className="mt-2 block text-xs text-slate-500">{audienceLabels[item.audience]}{item.category ? ` · ${item.category}` : ""}</span>
                    </button>
                  </li>
                ))}
              </ul>
            )}

            {lastPage > 1 && <div className="flex items-center justify-between border-t border-slate-200 px-4 py-3">
              <button type="button" aria-label="Página anterior" disabled={page <= 1 || loading} onClick={() => void loadArticles(page - 1)} className="grid size-11 place-items-center rounded-lg border border-slate-300 text-slate-700 disabled:cursor-not-allowed disabled:opacity-40"><ChevronLeft size={18} aria-hidden="true" /></button>
              <span className="text-sm text-slate-600">Página {page} de {lastPage}</span>
              <button type="button" aria-label="Próxima página" disabled={page >= lastPage || loading} onClick={() => void loadArticles(page + 1)} className="grid size-11 place-items-center rounded-lg border border-slate-300 text-slate-700 disabled:cursor-not-allowed disabled:opacity-40"><ChevronRight size={18} aria-hidden="true" /></button>
            </div>}
          </section>

          <section aria-labelledby="editor-heading" className="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div className="border-b border-slate-200 px-4 py-4 sm:px-6">
              <p className="text-xs font-semibold uppercase tracking-[0.14em] text-emerald-800">Editor de conteúdo</p>
              <h2 id="editor-heading" className="mt-1 text-lg font-bold text-slate-950">{article ? "Revisar orientação" : "Nova orientação"}</h2>
              {article && <p className="mt-1 text-sm text-slate-600">Situação atual: {statusLabels[article.status]} · Público: {audienceLabels[article.audience]}</p>}
            </div>

            {article && <div className="space-y-3 border-b border-slate-200 bg-slate-50 p-4 sm:px-6">
              {article.published_version && <div className="rounded-xl border border-emerald-200 bg-white p-4">
                <div className="flex flex-wrap items-center gap-2">
                  <h3 className="text-sm font-bold text-slate-900">Última versão publicada · v{article.published_version.version}</h3>
                  <span className={`rounded-full px-2 py-0.5 text-xs font-semibold ${article.publication_active ? "bg-emerald-100 text-emerald-900" : "bg-slate-200 text-slate-800"}`}>{article.publication_active ? "Ativa" : "Desativada"}</span>
                </div>
                <p className="mt-2 font-semibold text-slate-800">{article.published_version.title}</p>
                <p className="mt-1 text-sm leading-5 text-slate-600">{article.published_version.summary}</p>
                {article.published_version.published_at && <p className="mt-2 text-xs text-slate-500">Publicada em {new Date(article.published_version.published_at).toLocaleString("pt-BR", { dateStyle: "short", timeStyle: "short" })}</p>}
              </div>}

              {pendingVersion && ["draft", "review"].includes(article.status) && <div className="rounded-xl border border-amber-300 bg-amber-50 p-4">
                <h3 className="text-sm font-bold text-amber-950">Versão pendente · v{pendingVersion.version} · {statusLabels[pendingVersion.status]}</h3>
                <p className="mt-1 text-sm text-amber-950">Este conteúdo ainda não substitui a publicação acima.</p>
              </div>}

              {article.versions?.length > 0 && <details className="rounded-xl border border-slate-200 bg-white">
                <summary className="min-h-11 cursor-pointer px-4 py-3 text-sm font-semibold text-slate-800 focus-visible:outline-emerald-700">Histórico de versões ({article.versions.length})</summary>
                <ol className="max-h-64 divide-y divide-slate-100 overflow-y-auto px-4">
                  {[...article.versions].reverse().map((version) => <li key={version.id} className="py-3 text-sm">
                    <div className="flex flex-wrap items-center justify-between gap-2">
                      <span className="font-semibold text-slate-900">v{version.version} · {statusLabels[version.status]}</span>
                      <time className="text-xs text-slate-500" dateTime={version.created_at}>{new Date(version.created_at).toLocaleString("pt-BR", { dateStyle: "short", timeStyle: "short" })}</time>
                    </div>
                    <p className="mt-1 text-slate-700">{version.title}</p>
                    <p className="mt-1 text-xs text-slate-600">{versionEventLabels[version.event] ?? version.event} · {audienceLabels[version.audience]}{version.category ? ` · ${version.category}` : ""}</p>
                    <p className="mt-1 text-xs text-slate-600">Autor: {version.created_by_name ?? "Registro legado sem autoria identificada"} · Ação: {version.acted_by_name ?? "não identificada"}{version.reviewed_by_name ? ` · Revisão/publicação: ${version.reviewed_by_name}` : ""}</p>
                    {version.source_reference && <p className="mt-1 break-words text-xs text-slate-600">Referência informada: {version.source_reference}</p>}
                    <details className="mt-2 rounded-lg bg-slate-50 px-3 py-2">
                      <summary className="min-h-10 cursor-pointer py-2 text-xs font-semibold text-emerald-900 focus-visible:outline-emerald-700">Ver conteúdo desta versão</summary>
                      <div className="space-y-2 pb-2 text-sm leading-6 text-slate-700">
                        <p>{version.summary}</p>
                        <pre className="whitespace-pre-wrap break-words font-sans">{version.content}</pre>
                      </div>
                    </details>
                  </li>)}
                </ol>
              </details>}
            </div>}

            <form onSubmit={(event) => { event.preventDefault(); void save("draft"); }} className="space-y-4 p-4 sm:p-6">
              <label className="block">
                <span className="mb-1.5 block text-sm font-semibold text-slate-800">Título</span>
                <input required maxLength={180} value={form.title} onChange={(event) => setForm({ ...form, title: event.target.value })} className="min-h-11 w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 focus-visible:outline-emerald-700" />
              </label>
              <label className="block">
                <span className="mb-1.5 block text-sm font-semibold text-slate-800">Resumo</span>
                <textarea required maxLength={500} rows={2} value={form.summary} onChange={(event) => setForm({ ...form, summary: event.target.value })} className="w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 focus-visible:outline-emerald-700" />
              </label>
              <label className="block">
                <span className="mb-1.5 block text-sm font-semibold text-slate-800">Orientação em texto simples</span>
                <textarea required maxLength={20000} rows={8} value={form.content} onChange={(event) => setForm({ ...form, content: event.target.value })} aria-describedby="article-content-help" className="w-full resize-y rounded-lg border border-slate-300 px-3 py-2 leading-6 text-slate-900 focus-visible:outline-emerald-700" />
                <span id="article-content-help" className="mt-1 block text-xs leading-5 text-slate-600">HTML não é executado. Não inclua dados pessoais, informações de alunos ou regras sem validação.</span>
              </label>
              <div className="grid gap-4 sm:grid-cols-2">
                <label className="block">
                  <span className="mb-1.5 block text-sm font-semibold text-slate-800">Categoria</span>
                  <input maxLength={100} value={form.category} onChange={(event) => setForm({ ...form, category: event.target.value })} className="min-h-11 w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 focus-visible:outline-emerald-700" />
                </label>
                <label className="block">
                  <span className="mb-1.5 block text-sm font-semibold text-slate-800">Público</span>
                  <select value={form.audience} onChange={(event) => setForm({ ...form, audience: event.target.value as ArticleAudience })} className="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 focus-visible:outline-emerald-700">
                    {Object.entries(audienceLabels).map(([value, label]) => <option key={value} value={value}>{label}</option>)}
                  </select>
                </label>
              </div>
              <label className="block">
                <span className="mb-1.5 block text-sm font-semibold text-slate-800">Fonte ou referência</span>
                <input maxLength={255} value={form.source_reference} onChange={(event) => setForm({ ...form, source_reference: event.target.value })} aria-describedby="article-source-help" className="min-h-11 w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 focus-visible:outline-emerald-700" />
                <span id="article-source-help" className="mt-1 block text-xs leading-5 text-slate-600">Referência informativa; o texto não comprova aprovação institucional. A publicação depende de uma confirmação explícita de revisão.</span>
              </label>

              {article && <p className="rounded-lg bg-slate-50 px-3 py-2 text-xs leading-5 text-slate-600">Última alteração: {new Date(article.updated_at).toLocaleString("pt-BR", { dateStyle: "short", timeStyle: "short" })}. Salvar uma nova revisão mantém a última versão publicada visível até uma nova publicação ou desativação.</p>}

              {article?.status === "review" && article.can_publish && <label className="flex items-start gap-3 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm leading-5 text-emerald-950">
                <input type="checkbox" checked={publicationConfirmed} onChange={(event) => setPublicationConfirmed(event.target.checked)} className="mt-0.5 size-4 accent-emerald-800" />
                <span>Confirmo que revisei o conteúdo e que a publicação está autorizada. A referência informada, sozinha, não comprova aprovação.</span>
              </label>}

              <div className="flex flex-wrap gap-2 border-t border-slate-100 pt-4">
                <button type="submit" disabled={saving} className="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-[#004d40] px-4 text-sm font-semibold text-white transition hover:bg-emerald-900 disabled:cursor-wait disabled:opacity-60">
                  {saving ? <LoaderCircle size={16} className="animate-spin" aria-hidden="true" /> : <Save size={16} aria-hidden="true" />}
                  Salvar rascunho
                </button>
                {article?.status === "draft" && <button type="button" disabled={saving} onClick={(event) => { if (event.currentTarget.form?.reportValidity()) void save("review"); }} className="min-h-11 rounded-lg border border-amber-400 bg-amber-50 px-4 text-sm font-semibold text-amber-950 transition hover:bg-amber-100 disabled:opacity-60">Enviar para revisão</button>}
                {article?.status === "review" && article.can_publish && <button type="button" disabled={saving} onClick={(event) => { if (event.currentTarget.form?.reportValidity()) void save("published"); }} className="min-h-11 rounded-lg border border-emerald-800 px-4 text-sm font-semibold text-emerald-900 transition hover:bg-emerald-50 disabled:opacity-60">Publicar versão revisada</button>}
                {article && article.status !== "inactive" && article.can_publish && <button type="button" disabled={saving} onClick={(event) => { if (event.currentTarget.form?.reportValidity()) void save("inactive"); }} className="min-h-11 rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 disabled:opacity-60">Desativar</button>}
              </div>
              <p className="text-xs leading-5 text-slate-500">Só administradores globais ativos, sem escopos, podem publicar ou desativar. Artigos públicos e autenticados usam feeds separados; conteúdos internos nunca aparecem nesses feeds.</p>
            </form>
          </section>
        </div>
      </div>
    </div>
  );
}
