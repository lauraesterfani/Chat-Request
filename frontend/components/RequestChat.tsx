"use client";

import { FormEvent, useCallback, useEffect, useRef, useState } from "react";

type ChatMessage = { id: string; content: string; sender: { name: string; role: string }; is_mine: boolean; created_at: string };
type ResponseTemplate = { id: string; title: string; content: string };

export default function RequestChat({ requestId, status, templates = [] }: { requestId: string; status: string; templates?: ResponseTemplate[] }) {
  const [messages, setMessages] = useState<ChatMessage[]>([]);
  const [text, setText] = useState("");
  const [loading, setLoading] = useState(true);
  const [sending, setSending] = useState(false);
  const [error, setError] = useState("");
  const timer = useRef<ReturnType<typeof setInterval> | null>(null);
  const closed = ["completed", "canceled"].includes(status);

  const load = useCallback(async (markRead = false) => {
    const token = sessionStorage.getItem("jwt_token");
    if (!token) return;
    try {
      const response = await fetch(`/api/requests/${requestId}/messages?per_page=100`, { headers: { Authorization: `Bearer ${token}` } });
      if (!response.ok) throw new Error("Não foi possível carregar as mensagens.");
      const data = await response.json();
      setMessages((current) => {
        const incoming = data.data ?? [];
        const byId = new Map(current.map((item: ChatMessage) => [item.id, item]));
        incoming.forEach((item: ChatMessage) => byId.set(item.id, item));
        return Array.from(byId.values()).sort((a, b) => a.created_at.localeCompare(b.created_at));
      });
      if (markRead) await fetch(`/api/requests/${requestId}/messages/read`, { method: "POST", headers: { Authorization: `Bearer ${token}` } });
      setError("");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Erro ao carregar o chat.");
    } finally { setLoading(false); }
  }, [requestId]);

  useEffect(() => {
    load(true);
    timer.current = setInterval(() => { if (document.visibilityState === "visible") load(); }, 5000);
    return () => { if (timer.current) clearInterval(timer.current); };
  }, [load]);

  async function send(event: FormEvent) {
    event.preventDefault();
    if (sending || closed || !text.trim()) return;
    const content = text;
    setSending(true); setError("");
    try {
      const token = sessionStorage.getItem("jwt_token");
      const response = await fetch(`/api/requests/${requestId}/messages`, { method: "POST", headers: { Authorization: `Bearer ${token}`, "Content-Type": "application/json" }, body: JSON.stringify({ content }) });
      if (!response.ok) { const body = await response.json().catch(() => ({})); throw new Error(body.message || "Não foi possível enviar a mensagem."); }
      const created = await response.json();
      setMessages((current) => current.some((item) => item.id === created.id) ? current : [...current, created]);
      setText("");
    } catch (err) { setError(err instanceof Error ? err.message : "Erro ao enviar a mensagem."); }
    finally { setSending(false); }
  }

  return <section className="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm" aria-label="Histórico de atendimento">
    <h3 className="mb-4 text-xs font-black uppercase tracking-widest text-emerald-800">Histórico de atendimento</h3>
    <div className="max-h-80 space-y-3 overflow-y-auto rounded-xl bg-gray-50 p-3">
      {loading && <p className="text-sm text-gray-400">Carregando mensagens...</p>}
      {!loading && messages.length === 0 && <p className="text-sm text-gray-400">Ainda não há mensagens. Envie uma mensagem para iniciar o atendimento.</p>}
      {messages.map((message) => <div key={message.id} className={`flex ${message.is_mine ? "justify-end" : "justify-start"}`}>
        <div className={`max-w-[85%] rounded-2xl px-4 py-2 text-sm ${message.is_mine ? "bg-emerald-700 text-white" : "bg-white text-gray-700 border border-gray-100"}`}>
          <p className="mb-1 text-[10px] font-bold opacity-75">{message.sender.name} · {message.sender.role === "student" ? "Aluno" : "Equipe"}</p>
          <p className="whitespace-pre-wrap break-words">{message.content}</p>
          <time className="mt-1 block text-[10px] opacity-70">{new Date(message.created_at).toLocaleString("pt-BR")}</time>
        </div>
      </div>)}
    </div>
    {closed ? <p className="mt-3 rounded-lg bg-gray-100 p-3 text-center text-xs text-gray-500">Este requerimento está encerrado. O histórico permanece disponível somente para leitura.</p> : <>
      {templates.length > 0 && <label className="mt-3 block text-xs font-bold text-emerald-800">Resposta pré-configurada
        <select defaultValue="" onChange={(event) => { const template = templates.find((item) => item.id === event.target.value); if (template && (!text.trim() || window.confirm("Substituir o texto atual pela resposta pré-configurada?"))) setText(template.content); }} className="mt-1 w-full rounded-xl border border-emerald-100 bg-emerald-50/50 p-2 text-sm font-normal text-slate-700">
          <option value="">Selecionar texto-base...</option>
          {templates.map((template) => <option key={template.id} value={template.id}>{template.title}</option>)}
        </select>
      </label>}
      <form onSubmit={send} className="mt-3 flex gap-2">
      <textarea value={text} onChange={(event) => setText(event.target.value)} onKeyDown={(event) => { if (event.key === "Enter" && !event.shiftKey) { event.preventDefault(); void send(event); } }} maxLength={2000} rows={2} aria-label="Mensagem" placeholder="Digite sua mensagem... (Enter envia, Shift+Enter quebra linha)" className="min-w-0 flex-1 resize-none rounded-xl border border-gray-200 p-3 text-sm outline-none focus:ring-2 focus:ring-emerald-500" />
      <button disabled={sending || !text.trim()} className="self-end rounded-xl bg-emerald-700 px-4 py-3 text-xs font-bold text-white disabled:opacity-50">{sending ? "Enviando..." : "Enviar"}</button>
      </form>
    </>}
    {error && <p role="alert" className="mt-2 text-xs text-red-600">{error}</p>}
  </section>;
}
