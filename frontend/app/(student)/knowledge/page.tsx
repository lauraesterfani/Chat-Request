"use client";

import Link from "next/link";
import { useCallback, useEffect, useMemo, useState } from "react";
import { ArrowLeft, BookOpen, Search } from "lucide-react";
import { useAuth } from "../../context/AuthContext";

type KnowledgeArticle = {
  id: string;
  title: string;
  summary: string;
  content: string;
  category: string | null;
  source_reference: string | null;
  published_at: string | null;
};

export default function StudentKnowledgePage() {
  const { token, isLoading: authLoading } = useAuth();
  const [articles, setArticles] = useState<KnowledgeArticle[]>([]);
  const [query, setQuery] = useState("");
  const [category, setCategory] = useState("");
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  const loadArticles = useCallback(async (signal?: AbortSignal) => {
    if (authLoading) return;
    if (!token) {
      setError("Entre na sua conta de aluno para consultar estas orientações.");
      setLoading(false);
      return;
    }
    setLoading(true);
    setError("");
    try {
      const response = await fetch("/api/knowledge-articles/authenticated", {
        headers: { Accept: "application/json", Authorization: `Bearer ${token}` },
        signal,
      });
      if (response.status === 401) throw new Error("Sua sessão expirou. Entre novamente para consultar as orientações.");
      if (response.status === 403) throw new Error("Esta área está disponível somente para contas de aluno.");
      if (!response.ok) throw new Error("Não foi possível carregar as orientações agora.");
      const result = await response.json() as KnowledgeArticle[];
      setArticles(Array.isArray(result) ? result : []);
    } catch (reason) {
      if (reason instanceof DOMException && reason.name === "AbortError") return;
      setError(reason instanceof Error ? reason.message : "Não foi possível carregar as orientações agora.");
    } finally {
      if (!signal?.aborted) setLoading(false);
    }
  }, [authLoading, token]);

  useEffect(() => {
    const controller = new AbortController();
    void loadArticles(controller.signal);
    return () => controller.abort();
  }, [loadArticles]);

  const categories = useMemo(() => [...new Set(articles.map((article) => article.category?.trim()).filter((value): value is string => Boolean(value)))].sort((a, b) => a.localeCompare(b, "pt-BR")), [articles]);
  const filteredArticles = useMemo(() => {
    const term = query.trim().toLocaleLowerCase("pt-BR");
    return articles.filter((article) => (!category || article.category === category)
      && (!term || [article.title, article.summary, article.content, article.category].filter(Boolean).join(" ").toLocaleLowerCase("pt-BR").includes(term)));
  }, [articles, query, category]);

  return (
    <main id="conteudo-principal" tabIndex={-1} className="min-h-[70vh] bg-slate-50 text-slate-900">
      <header className="border-b border-emerald-100 bg-white">
        <div className="mx-auto flex max-w-5xl items-center gap-4 px-4 py-5 sm:px-8">
          <Link href="/me" aria-label="Voltar ao início" className="inline-flex min-h-11 min-w-11 items-center justify-center rounded-xl border border-slate-200 text-slate-700 transition hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800"><ArrowLeft size={19} aria-hidden="true" /></Link>
          <div className="flex items-center gap-3">
            <span className="rounded-xl bg-emerald-50 p-2 text-emerald-800"><BookOpen size={21} aria-hidden="true" /></span>
            <div><p className="text-xs font-semibold uppercase tracking-[0.12em] text-emerald-800">Secretaria virtual</p><h1 className="text-xl font-bold tracking-tight sm:text-2xl">Orientações para estudantes</h1></div>
          </div>
        </div>
      </header>

      <section className="border-b border-slate-200 bg-white px-4 py-7 sm:px-8 sm:py-9">
        <div className="mx-auto max-w-5xl">
          <p className="max-w-2xl leading-7 text-slate-700">Consulte orientações publicadas para alunos. As respostas são apresentadas como texto e podem ser filtradas por assunto.</p>
          <div className="mt-5 grid gap-3 sm:grid-cols-[minmax(0,1fr)_240px]">
            <label className="relative block">
              <span className="sr-only">Buscar nas orientações</span>
              <Search aria-hidden="true" className="absolute left-4 top-1/2 -translate-y-1/2 text-slate-600" size={19} />
              <input type="search" value={query} onChange={(event) => setQuery(event.target.value)} placeholder="Buscar por palavra ou assunto" className="min-h-12 w-full rounded-xl border border-slate-300 bg-white py-3 pl-12 pr-4 text-base text-slate-900 placeholder:text-slate-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" />
            </label>
            <label className="block">
              <span className="sr-only">Filtrar orientações por categoria</span>
              <select value={category} onChange={(event) => setCategory(event.target.value)} className="min-h-12 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-base text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800">
                <option value="">Todas as categorias</option>
                {categories.map((item) => <option key={item} value={item}>{item}</option>)}
              </select>
            </label>
          </div>
        </div>
      </section>

      <section aria-label="Orientações publicadas para estudantes" className="mx-auto max-w-5xl px-4 py-7 sm:px-8 sm:py-10">
        {loading && <div role="status" aria-live="polite" className="rounded-2xl border border-slate-200 bg-white p-6 text-slate-700 shadow-sm">Carregando orientações…</div>}
        {!loading && error && <div role="alert" className="rounded-2xl border border-red-200 bg-red-50 p-6 text-red-900"><p className="font-semibold">{error}</p><button type="button" onClick={() => void loadArticles()} className="mt-4 min-h-11 rounded-lg border border-red-300 bg-white px-4 text-sm font-semibold text-red-900 underline-offset-2 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-800">Tentar novamente</button></div>}
        {!loading && !error && articles.length === 0 && <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><h2 className="font-semibold text-slate-900">Ainda não há orientações para estudantes</h2><p className="mt-2 leading-6 text-slate-700">Novas respostas serão exibidas aqui depois de serem revisadas e publicadas.</p></div>}
        {!loading && !error && articles.length > 0 && filteredArticles.length === 0 && <div role="status" className="rounded-2xl border border-slate-200 bg-white p-6 text-slate-700 shadow-sm">Nenhuma orientação corresponde à busca. Altere o termo ou selecione outra categoria.</div>}
        {!loading && !error && filteredArticles.length > 0 && <div className="divide-y divide-slate-200 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
          {filteredArticles.map((article) => <article key={article.id} className="px-5 py-5 sm:px-7 sm:py-6">
            <div className="flex flex-wrap items-center justify-between gap-2">
              <p className="text-xs font-bold uppercase tracking-[0.1em] text-emerald-800">{article.category || "Orientação acadêmica"}</p>
              {article.published_at && <time dateTime={article.published_at} className="text-xs text-slate-600">Atualizado em {new Date(article.published_at).toLocaleDateString("pt-BR", { timeZone: "UTC" })}</time>}
            </div>
            <h2 className="mt-2 text-lg font-bold leading-snug text-slate-900 sm:text-xl">{article.title}</h2>
            <p className="mt-2 leading-7 text-slate-700">{article.summary}</p>
            <details className="group mt-4 border-t border-slate-100 pt-3">
              <summary className="flex min-h-11 cursor-pointer list-none items-center justify-between gap-4 font-semibold text-emerald-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800 [&::-webkit-details-marker]:hidden">
                <span>Consultar orientação completa</span><span aria-hidden="true" className="text-xl transition-transform group-open:rotate-45">+</span>
              </summary>
              <div className="mt-3 whitespace-pre-wrap break-words border-l-2 border-emerald-700 pl-4 leading-7 text-slate-800">{article.content}</div>
              {article.source_reference && <p className="mt-4 border-t border-slate-100 pt-3 text-xs leading-5 text-slate-600">Referência informada: {article.source_reference}</p>}
            </details>
          </article>)}
        </div>}
      </section>
    </main>
  );
}
