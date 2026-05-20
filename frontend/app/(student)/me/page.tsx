'use client';

import { useState, useRef, useEffect } from 'react';
import { useAuth } from '../../context/AuthContext';
import { Send, Paperclip, XCircle, Loader2, FilePlus2, ClipboardList, CheckCircle2 } from 'lucide-react';
import { FaWhatsapp } from "react-icons/fa";

const API_BASE = "/api";

interface Message {
  id: string | number;
  role: "bot" | "user";
  text: React.ReactNode;
  options?: { label: string; action: string; icon?: React.ReactNode; value?: any }[];
  items?: { subject: string; status: string }[];
}

export default function GuidedChatPage() {
  const { token, user } = useAuth();
  const bottomRef = useRef<HTMLDivElement>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);

  const [messages, setMessages] = useState<Message[]>([]);
  const [loading, setLoading] = useState(false);
  const [step, setStep] = useState<"idle" | "description" | "waiting_file">("idle");
  const [inputValue, setInputValue] = useState("");
  const [files, setFiles] = useState<File[]>([]);
  const [tempData, setTempData] = useState({
    typeId: "",
    typeName: "",
    description: "",
    minAttachments: 0,
    attachmentInstructions: ""
  });

  const initialOptions = [
    { label: " Novo Requerimento", action: "start_flow", icon: <FilePlus2 size={16} /> },
    { label: " Meus Pedidos", action: "view_requests", icon: <ClipboardList size={16} /> },
  ];

  // Efeitos
  useEffect(() => {
    if (user) {
      setMessages([{
        id: "init",
        role: "bot",
        text: (
          <span>
            Olá, <span className="text-[#15803d] font-semibold">{user.name.split(" ")[0]}</span>! Sou o assistente virtual. Como posso ajudar você hoje?
          </span>
        ),
        options: initialOptions,
      }]);
    }
  }, [user]);

  useEffect(() => {
    bottomRef.current?.scrollIntoView({ behavior: "smooth" });
  }, [messages, loading]);

  // Handlers de Arquivo
  const handleFileSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files) {
      const selected = Array.from(e.target.files);
      setFiles((prev) => [...prev, ...selected]);
    }
  };

  const removeFile = (index: number) => {
    setFiles((prev) => prev.filter((_, i) => i !== index));
  };

  const clearAllFiles = () => {
    setFiles([]);
  };

  const cancelFlow = () => {
    setStep("idle");
    setFiles([]);
    setMessages((prev) => [
      ...prev,
      { id: Date.now(), role: "bot", text: "A operação foi cancelada. Posso ajudar em algo mais?", options: initialOptions },
    ]);
  };

  const finalizeRequest = async (currentDescription?: string) => {
    if (loading) return;
    const finalDescription = currentDescription || tempData.description;
    if (!finalDescription.trim()) return;

    setLoading(true);
    try {
      const documentIds: string[] = [];
      if (files.length > 0) {
        for (const file of files) {
          const formData = new FormData();
          formData.append("arquivo", file);
          const uploadRes = await fetch(`${API_BASE}/documents/upload`, {
            method: "POST",
            headers: { Authorization: `Bearer ${token}` },
            body: formData,
          });
          const uploadData = await uploadRes.json();
          documentIds.push(uploadData.id);
        }
      }

      await fetch(`${API_BASE}/requests`, {
        method: "POST",
        headers: { Authorization: `Bearer ${token}`, "Content-Type": "application/json" },
        body: JSON.stringify({
          type_id: tempData.typeId,
          subject: tempData.typeName,
          description: finalDescription,
          document_ids: documentIds,
        }),
      });

      setMessages((prev) => [
        ...prev,
        { id: Date.now(), role: "bot", text: (
          <div className="flex items-center gap-2">
            <CheckCircle2 size={18} className="text-[#15803d]" />
            <span>Seu requerimento de "{tempData.typeName}" foi enviado com sucesso!</span>
          </div>
        )},
        { id: Date.now() + 1, role: "bot", text: "Deseja realizar mais alguma operação?", options: initialOptions },
      ]);

      setStep("idle");
      setFiles([]);
      setTempData({ typeId: "", typeName: "", description: "", minAttachments: 0, attachmentInstructions: "" });
    } catch {
      setMessages((prev) => [...prev, { id: Date.now(), role: "bot", text: "Houve um erro no envio. Por favor, tente novamente." }]);
    } finally {
      setLoading(false);
    }
  };

  const handleSendMessage = (e?: React.FormEvent) => {
    if (e) e.preventDefault();
    if (!inputValue.trim() || step !== "description") return;

    const text = inputValue;
    setInputValue("");
    setMessages((prev) => [...prev, { id: Date.now(), role: "user", text }]);
    setTempData((prev) => ({ ...prev, description: text }));

    // Se o requerimento não precisa de anexos, finaliza direto
    if (tempData.minAttachments === 0) {
      setStep("idle");
      finalizeRequest(text);
    } else {
      setStep("waiting_file");
      setMessages((prev) => [
        ...prev,
        {
          id: Date.now() + 1,
          role: "bot",
          text: `📎 Por favor, realize o envio dos arquivos solicitados:\n${tempData.attachmentInstructions}`,
        },
      ]);
    }
  };

  const handleAction = async (opt: any) => {
    if (opt.action === "start_flow") {
      const res = await fetch(`${API_BASE}/type-requests`);
      const data = await res.json();
      const allTypes = data.data || data || [];

      setMessages((prev) => [
        ...prev,
        { id: Date.now(), role: "user", text: opt.label },
        {
          id: Date.now() + 1,
          role: "bot",
          text: "Qual requerimento você deseja abrir?",
          options: Array.isArray(allTypes) ? allTypes.map((t: any) => ({
            label: t.name,
            value: t.id,
            action: "select_type",
            descriptionRaw: t.description // Passa a descrição vinda do banco
          })) : []
        },
      ]);
    } else if (opt.action === "select_type") {
      const rawDescription = opt.descriptionRaw || "";
      
      // 🔮 LÓGICA DO PONTO E VÍRGULA: Divide a string no ';'
      const parts = rawDescription.split(";");
      
      const botMessage = parts[0]?.trim(); // O que está ANTES do ';' é a pergunta
      const documentsRequired = parts[1]?.trim(); // O que está DEPOIS do ';' é o anexo

      // Se houver texto válido após o ';', ativa a necessidade de anexo
      const hasAttachment = documentsRequired ? 1 : 0;

      setTempData({
        typeId: opt.value,
        typeName: opt.label,
        description: "",
        minAttachments: hasAttachment,
        attachmentInstructions: documentsRequired || "",
      });

      setStep("description");
      setMessages((prev) => [
        ...prev,
        { id: Date.now(), role: "user", text: opt.label },
        {
          id: Date.now() + 1,
          role: "bot",
          text: botMessage || "Por favor, descreva o motivo da sua solicitação:"
        },
      ]);
    } else if (opt.action === "view_requests") {
      try {
        const res = await fetch(`${API_BASE}/requests`, {
          headers: { Authorization: `Bearer ${token}` },
        });
        const data = await res.json();
        const requests = data.data || data || [];
        const statusMap: any = { pending: "Pendente", analyzing: "Em análise", completed: "Concluído", canceled: "Cancelado", denied: "Negado" };

        setMessages((prev) => [
          ...prev,
          { id: Date.now(), role: "user", text: opt.label },
          {
            id: Date.now() + 1,
            role: "bot",
            text: "Aqui estão os seus pedidos recentes:",
            items: requests.map((req: any) => ({
              subject: req.subject,
              status: statusMap[req.status?.toLowerCase()] || req.status,
            })),
            options: initialOptions,
          },
        ]);
      } catch {
        setMessages((prev) => [...prev, { id: Date.now(), role: "bot", text: "Não foi possível carregar seu histórico." }]);
      }
    }
  };

  return (
    <div className="flex flex-col h-[100dvh] bg-[#f8fafc] font-sans text-slate-700 antialiased">
      
      {/* 🔹 ÁREA DE MENSAGENS */}
      <main className="flex-1 overflow-y-auto px-4 py-8 space-y-8 max-w-4xl mx-auto w-full scrollbar-hide">
        {messages.map((msg) => (
          <div key={msg.id} className={`flex flex-col ${msg.role === "user" ? "items-end" : "items-start"} w-full animate-fade-in-up`}>
            {msg.role === "bot" && (
              <div className="flex items-center gap-2 mb-2 ml-1">
                <div className="w-8 h-8 rounded-full border border-gray-100 flex items-center justify-center overflow-hidden bg-white shadow-sm">
                  <img src="/jacareu.jpg" alt="Jacaréu" className="w-full h-full object-cover" />
                </div>
                <span className="text-[10px] font-semibold text-[#15803d] tracking-widest uppercase">Secretaria Virtual</span>
              </div>
            )}

            <div className={`p-5 shadow-sm transition-all text-base leading-relaxed whitespace-pre-line
              ${msg.role === "user"
                ? "bg-[#15803d] text-white rounded-3xl rounded-tr-none max-w-[85%]"
                : "bg-white border border-gray-100 text-slate-700 rounded-3xl rounded-tl-none max-w-[90%]"
              }`}
            >
              {msg.text}

              {msg.items && (
                <div className="mt-4 space-y-3">
                  {msg.items.map((req, i) => (
                    <div key={i} className="flex justify-between items-center bg-slate-50 p-4 rounded-2xl border border-gray-100">
                      <span className="text-sm font-medium text-slate-600 italic">{req.subject}</span>
                      <span className={`text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full 
                        ${req.status === "Concluído" ? "bg-green-100 text-green-700" : 
                          req.status === "Em análise" ? "bg-amber-100 text-amber-700" : 
                          "bg-slate-200 text-slate-500"}`}>
                        {req.status}
                      </span>
                    </div>
                  ))}
                </div>
              )}

              {msg.options && (
                <div className="flex flex-wrap gap-2 mt-5">
                  {msg.options.map((opt, i) => (
                    <button
                      key={i}
                      onClick={() => handleAction(opt)}
                      className="flex items-center gap-2 px-5 py-2.5 bg-white border border-gray-200 text-slate-600 text-sm rounded-full hover:border-[#15803d] hover:text-[#15803d] transition-all transform active:scale-95"
                    >
                      {opt.icon} {opt.label}
                    </button>
                  ))}
                </div>
              )}
            </div>
          </div>
        ))}
        {loading && <div className="flex ml-4"><Loader2 className="animate-spin text-[#15803d] w-5 h-5" /></div>}
        <div ref={bottomRef} className="h-4" />
      </main>

      {/* 🔹 FOOTER */}
      <footer className="p-6 bg-white border-t border-gray-100 relative">
        <div className="max-w-4xl mx-auto">
          
          {/* Lista de Anexos */}
          {files.length > 0 && (
            <div className="flex flex-col gap-2 mb-4 animate-fade-in">
              {files.map((file, i) => (
                <div key={i} className="flex items-center justify-between bg-slate-50 p-3 rounded-xl border border-gray-200">
                  <span className="text-sm text-slate-600 truncate">{file.name}</span>
                  <button type="button" onClick={() => removeFile(i)} className="text-red-500"><XCircle size={18} /></button>
                </div>
              ))}
              <button type="button" onClick={clearAllFiles} className="self-end text-xs text-red-500 mt-1">Cancelar todos</button>
            </div>
          )}

          <form onSubmit={handleSendMessage} className="flex gap-3 items-center">
            {step !== "idle" && (
              <button type="button" onClick={cancelFlow} className="p-3 text-slate-300 hover:text-red-500"><XCircle size={24} /></button>
            )}

            {/* O clipe de anexo só aparece se o passo atual for o de esperar arquivos */}
            {step === "waiting_file" && (
              <>
                <input type="file" ref={fileInputRef} multiple onChange={handleFileSelect} className="hidden" />
                <button
                  type="button"
                  onClick={() => fileInputRef.current?.click()}
                  className="p-4 bg-[#f8fafc] text-[#15803d] rounded-2xl border border-gray-100 hover:bg-[#dcfce7]"
                >
                  <Paperclip size={20} />
                </button>
              </>
            )}

            <input
              value={inputValue}
              onChange={(e) => setInputValue(e.target.value)}
              disabled={step !== "description"}
              placeholder={step === "waiting_file" ? `${files.length} arquivo(s) prontos...` : "Escreva sua mensagem aqui..."}
              className="flex-1 bg-[#f8fafc] border border-gray-100 rounded-2xl px-6 py-4 text-sm outline-none focus:border-[#15803d] transition-all"
            />

            {step === "waiting_file" ? (
              <button
                type="button"
                onClick={() => finalizeRequest(tempData.description)}
                className="px-6 py-4 bg-[#15803d] text-white rounded-2xl text-sm font-semibold hover:bg-[#166534] transition-all"
              >
                Enviar {files.length > 0 ? files.length : ''}
              </button>
            ) : (
              <button
                type="submit"
                disabled={step !== "description" || !inputValue.trim()}
                className="p-4 bg-[#15803d] text-white rounded-2xl disabled:bg-slate-100 disabled:text-slate-300 transition-all"
              >
                <Send size={20} />
              </button>
            )}
          </form>
        </div>
      </footer>

      {/* 🔹 WHATSAPP FLUTUANTE */}
      <a
        href="https://wa.me/5581988224907?text=Olá,%20gostaria%20de%20falar%20com%20a%20CRADT."
        target="_blank"
        rel="noopener noreferrer"
        className="fixed bottom-28 right-6 bg-green-500 hover:bg-green-600 text-white px-4 py-3 rounded-full shadow-lg flex items-center gap-2 transition-all z-50 transform hover:scale-105"
      >
        <FaWhatsapp className="w-6 h-6" />
        <span className="font-semibold text-sm">Falar com a CRADT</span>
      </a>
    </div>
  );
}