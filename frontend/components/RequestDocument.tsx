"use client";

import { useEffect, useState } from "react";
import { ExternalLink, FileText } from "lucide-react";

export type DocumentInfo = { id: string; name: string; mime_type: string };

export default function RequestDocument({ requestId, document }: { requestId: string; document: DocumentInfo }) {
  const [url, setUrl] = useState<string | null>(null);
  const [error, setError] = useState(false);
  const isImage = document.mime_type.startsWith("image/");

  useEffect(() => {
    const controller = new AbortController();
    let objectUrl: string | null = null;
    const token = sessionStorage.getItem("jwt_token");
    if (!token) return;

    fetch(`/api/requests/${requestId}/documents/${document.id}`, {
      headers: { Authorization: `Bearer ${token}` },
      signal: controller.signal,
    }).then(async (response) => {
      if (!response.ok) throw new Error("Documento indisponível");
      objectUrl = URL.createObjectURL(await response.blob());
      setUrl(objectUrl);
    }).catch(() => { if (!controller.signal.aborted) setError(true); });

    return () => { controller.abort(); if (objectUrl) URL.revokeObjectURL(objectUrl); };
  }, [requestId, document.id]);

  return <div className="group flex flex-col gap-3 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm transition-colors hover:border-emerald-200">
    {url && isImage ? <a href={url} target="_blank" rel="noopener noreferrer" className="block cursor-zoom-in overflow-hidden rounded-xl border bg-gray-50">
      {/* A URL blob: autenticada não passa pelo otimizador remoto de imagens do Next. */}
      {/* eslint-disable-next-line @next/next/no-img-element */}
      <img src={url} alt={document.name} className="h-48 w-full object-contain" />
    </a> :
      <div className="flex items-center gap-3 rounded-xl border border-dashed bg-gray-50 p-6"><FileText className="text-emerald-700" /><span className="text-sm font-semibold text-slate-600">{error ? "Documento indisponível" : url ? "Documento PDF" : "Carregando documento…"}</span></div>}
    <div className="flex items-center justify-between gap-3 px-1"><span className="truncate text-sm font-bold text-gray-700" title={document.name}>{document.name}</span>{url && <a href={url} target="_blank" rel="noopener noreferrer" className="inline-flex shrink-0 items-center gap-1 text-xs font-bold text-emerald-700 hover:underline">ABRIR <ExternalLink size={14} /></a>}</div>
  </div>;
}
