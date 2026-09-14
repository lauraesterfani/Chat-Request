"use client";

import Link from "next/link";
import { useEffect, useMemo, useState } from "react";
import { BookOpen, ExternalLink, FileText, Search } from "lucide-react";

type CatalogEntry = {
  id: string;
  name: string;
  description: string | null;
  category: string | null;
  audience: string | null;
  channel: "digital" | "external";
  channel_instructions: string | null;
  responsible_sector: string | null;
  documentation: { required: boolean; instructions: string | null };
};

type KnowledgeArticle = {
  id: string;
  title: string;
  summary: string;
  content: string;
  category: string | null;
  source_reference: string | null;
  published_at: string | null;
};

async function getJson<T>(url: string): Promise<T> {
  const response = await fetch(url, { headers: { Accept: "application/json" } });
  if (!response.ok) throw new Error("Não foi possível carregar o conteúdo agora.");
  return response.json() as Promise<T>;
}

export default function ServicesPage() {
  const [services, setServices] = useState<CatalogEntry[]>([]);
  const [articles, setArticles] = useState<KnowledgeArticle[]>([]);
  const [query, setQuery] = useState("");
  const [category, setCategory] = useState("");
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    let active = true;
    Promise.all([getJson<CatalogEntry[]>("/api/service-catalog"), getJson<KnowledgeArticle[]>("/api/knowledge-articles")])
      .then(([catalog, knowledge]) => {
        if (!active) return;
        setServices(catalog);
        setArticles(knowledge);
      })
      .catch((reason: unknown) => active && setError(reason instanceof Error ? reason.message : "Não foi possível carregar o conteúdo agora."))
      .finally(() => active && setLoading(false));
    return () => { active = false; };
  }, []);

  const categories = useMemo(() => [...new Set(services.map((item) => item.category).filter((item): item is string => Boolean(item)))].sort(), [services]);
  const visibleServices = useMemo(() => {
    const term = query.trim().toLocaleLowerCase("pt-BR");
    return services.filter((service) => (!category || service.category === category) && (!term || [service.name, service.description, service.audience, service.responsible_sector].filter(Boolean).join(" ").toLocaleLowerCase("pt-BR").includes(term)));
  }, [services, query, category]);
  const visibleArticles = useMemo(() => {
    const term = query.trim().toLocaleLowerCase("pt-BR");
    return articles.filter((article) => !term || [article.title, article.summary, article.category].filter(Boolean).join(" ").toLocaleLowerCase("pt-BR").includes(term));
  }, [articles, query]);

  return (
    <main className="min-h-screen bg-slate-50 text-slate-900">
      <header className="border-b border-slate-200 bg-white">
        <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-5 py-5 sm:px-8">
          <Link href="/" className="text-xl font-bold tracking-tight text-emerald-800">Chat Request</Link>
          <Link href="/login" className="rounded-full bg-emerald-700 px-5 py-2 text-sm font-semibold text-white transition hover:bg-emerald-800">Acessar conta</Link>
        </div>
      </header>

      <section className="border-b border-emerald-100 bg-emerald-50 px-5 py-12 sm:px-8">
        <div className="mx-auto max-w-6xl">
          <p className="mb-2 text-sm font-semibold uppercase tracking-wide text-emerald-800">Orientações e serviços</p>
          <h1 className="max-w-3xl text-3xl font-bold tracking-tight sm:text-4xl">Encontre o serviço ou a orientação de que você precisa</h1>
          <p className="mt-4 max-w-3xl leading-7 text-slate-600">Consulte as informações publicadas. A disponibilidade e as regras de cada serviço são confirmadas no momento do envio.</p>
          <div className="mt-7 grid gap-3 sm:grid-cols-[1fr_220px]">
            <label className="relative block">
              <span className="sr-only">Buscar serviços e orientações</span>
              <Search aria-hidden="true" className="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500" size={20} />
              <input value={query} onChange={(event) => setQuery(event.target.value)} placeholder="Buscar por serviço ou orientação" className="w-full rounded-xl border border-slate-300 bg-white py-3 pl-12 pr-4 outline-none ring-emerald-700 transition focus:ring-2" />
            </label>
            <label>
              <span className="sr-only">Filtrar serviços por categoria</span>
              <select value={category} onChange={(event) => setCategory(event.target.value)} className="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 outline-none ring-emerald-700 transition focus:ring-2">
                <option value="">Todas as categorias</option>
                {categories.map((item) => <option key={item} value={item}>{item}</option>)}
              </select>
            </label>
          </div>
        </div>
      </section>

      <div className="mx-auto max-w-6xl px-5 py-10 sm:px-8">
        {loading && <p role="status" className="rounded-xl bg-white p-6 text-slate-600 shadow-sm">Carregando serviços e orientações...</p>}
        {!loading && error && <p role="alert" className="rounded-xl border border-red-200 bg-red-50 p-6 text-red-800">{error} Tente recarregar a página.</p>}
        {!loading && !error && <>
          <section aria-labelledby="services-title">
            <div className="mb-5 flex items-center gap-2"><FileText aria-hidden="true" className="text-emerald-700" /><h2 id="services-title" className="text-2xl font-bold">Catálogo de serviços</h2></div>
            {visibleServices.length === 0 ? <p className="rounded-xl bg-white p-6 text-slate-600 shadow-sm">Nenhum serviço publicado corresponde à sua busca.</p> : <div className="grid gap-5 md:grid-cols-2">
              {visibleServices.map((service) => <article key={service.id} className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div className="flex flex-wrap items-start justify-between gap-3"><div><p className="text-sm font-medium text-emerald-800">{service.category || "Serviço acadêmico"}</p><h3 className="mt-1 text-xl font-bold">{service.name}</h3></div><span className="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{service.channel === "digital" ? "Atendimento digital" : "Canal externo"}</span></div>
                {service.description && <p className="mt-4 leading-6 text-slate-600">{service.description}</p>}
                <dl className="mt-5 space-y-2 border-t border-slate-100 pt-4 text-sm"><div><dt className="inline font-semibold">Público: </dt><dd className="inline text-slate-600">{service.audience || "Conforme regras institucionais"}</dd></div><div><dt className="inline font-semibold">Documentação: </dt><dd className="inline text-slate-600">{service.documentation.required ? (service.documentation.instructions || "Consulte as exigências ao iniciar o requerimento.") : "Não há documentação obrigatória publicada."}</dd></div>{service.responsible_sector && <div><dt className="inline font-semibold">Setor responsável: </dt><dd className="inline text-slate-600">{service.responsible_sector}</dd></div>}</dl>
                {service.channel === "digital" ? <Link href="/login" className="mt-6 inline-flex items-center gap-2 font-semibold text-emerald-800 underline underline-offset-4 hover:text-emerald-950">Acessar para solicitar <ExternalLink size={16} aria-hidden="true" /></Link> : <p className="mt-6 rounded-lg bg-amber-50 p-3 text-sm leading-6 text-amber-950">{service.channel_instructions || "Este serviço utiliza um canal externo. Consulte a orientação da instituição antes de iniciar."}</p>}
              </article>)}
            </div>}
          </section>

          <section aria-labelledby="knowledge-title" className="mt-14">
            <div className="mb-5 flex items-center gap-2"><BookOpen aria-hidden="true" className="text-emerald-700" /><h2 id="knowledge-title" className="text-2xl font-bold">Orientações publicadas</h2></div>
            {visibleArticles.length === 0 ? <p className="rounded-xl bg-white p-6 text-slate-600 shadow-sm">Nenhuma orientação publicada corresponde à sua busca.</p> : <div className="grid gap-5 md:grid-cols-2">
              {visibleArticles.map((article) => <details key={article.id} className="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><summary className="cursor-pointer list-none pr-8 text-lg font-bold marker:hidden"><span>{article.title}</span><span aria-hidden="true" className="float-right text-emerald-800 transition group-open:rotate-45">+</span></summary><p className="mt-3 leading-6 text-slate-600">{article.summary}</p><div className="mt-4 whitespace-pre-line border-t border-slate-100 pt-4 leading-7 text-slate-700">{article.content}</div>{article.source_reference && <p className="mt-4 text-xs text-slate-500">Fonte informada: {article.source_reference}</p>}</details>)}
            </div>}
          </section>
        </>}
      </div>
    </main>
  );
}
