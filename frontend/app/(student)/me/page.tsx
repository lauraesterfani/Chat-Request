'use client';

import { useState, useRef, useEffect } from 'react';
import { useAuth } from '../../context/AuthContext';
import { Send, Paperclip, XCircle, Loader2, FilePlus2, ClipboardList, CheckCircle2 } from 'lucide-react';

const API_BASE = "http://127.0.0.1:8000/api";

const REQUEST_CONFIG: Record<string, {
    descriptionMessage: string;
    attachmentMessage?: string;
    minAttachments?: number;
}> = {
    "Admissão por Transferência e Análise Curricular (anexos) - Solicitação no Protocolo Geral": {
        descriptionMessage: "Relate os detalhes da transferência e análise curricular desejada.",
        attachmentMessage: "📎 Anexe: Declaração de Transferência, Históricos e Ementas das disciplinas. (c, f, g, h, i, a)",
        minAttachments: 1
    },
    "Ajuste de Matrícula Semestral": {
        descriptionMessage: "Descreva as inclusões ou exclusões de disciplinas para o semestre.",
    },
    "Autorização para cursar disciplinas em outras Instituições de Ensino Superior (especifique)": {
        descriptionMessage: "Especifique a Instituição e as disciplinas que deseja cursar.",
    },
    "Cancelamento de Matrícula": {
        descriptionMessage: "Informe o motivo do cancelamento definitivo do vínculo.",
    },
    "Cancelamento de Disciplina (especifique)": {
        descriptionMessage: "Especifique o nome da disciplina que deseja cancelar.",
    },
    "Certificado de Conclusão - Ano ( ) Semestre ( )": {
        descriptionMessage: "Informe o Ano e o Semestre de conclusão do curso.",
    },
    "Certidão - Autenticidade (especifique)": {
        descriptionMessage: "Especifique o documento para o qual deseja a certidão de autenticidade.",
    },
    "Complementação de Matrícula (especifique)": {
        descriptionMessage: "Especifique as disciplinas para complementação.",
    },
    "Cópia Xerox de Documento (especifique)": {
        descriptionMessage: "Especifique qual documento você deseja a cópia.",
    },
    "Declaração de Colação de Grau e Tramitação de Diploma (curso tecnológico)": {
        descriptionMessage: "Confirme a solicitação para curso tecnológico.",
        attachmentMessage: "📎 Anexe: Atestado Médico ou Cópia da CTPS e Declaração da Empresa. (a/b, d)",
        minAttachments: 1
    },
    "Declaração de Matrícula ou Matrícula Vínculo (especifique)": {
        descriptionMessage: "Especifique se deseja Declaração de Matrícula ou Vínculo.",
    },
    "Declaração de Monitoria": {
        descriptionMessage: "Informe o período e a disciplina da monitoria.",
    },
    "Declaração para Estágio - Conclusão Ano ( ) Semestre ( )": {
        descriptionMessage: "Informe o Ano e Semestre previstos para conclusão.",
    },
    "Diploma 1a Via ( ) 2a ( ) - Conclusão Ano ( ) Semestre ( )": {
        descriptionMessage: "Especifique a via (1ª ou 2ª) e o período de conclusão.",
    },
    "Dispensa da prática de Educação Física (anexos)": {
        descriptionMessage: "Informe o motivo da dispensa de Educação Física.",
        attachmentMessage: "📎 Anexe: Atestado Médico ou Declaração de Unidade Militar. (a/j)",
        minAttachments: 1
    },
    "Declaração Tramitação de Diploma (técnico)": {
        descriptionMessage: "Confirme a solicitação de tramitação para curso técnico.",
    },
    "Ementa de disciplina - (especifique)": {
        descriptionMessage: "Especifique o nome da disciplina para a ementa.",
    },
    "Guia de Transferência": {
        descriptionMessage: "Informe a instituição de destino para a transferência.",
    },
    "Histórico Escolar - Ano ( ) Semestre ( )": {
        descriptionMessage: "Informe o Ano e Semestre de referência para o histórico.",
    },
    "Isenção de disciplinas cursadas (anexo)": {
        descriptionMessage: "Relate as disciplinas para isenção.",
        attachmentMessage: "📎 Anexe: Histórico Escolar e Ementas das disciplinas cursadas. (f/g/h, i)",
        minAttachments: 1
    },
    "Justificativa de falta(s) ou prova 2o chamada (anexos)": {
        descriptionMessage: "Descreva o motivo e as datas das faltas ou provas.",
        attachmentMessage: "📎 Anexe: Atestado Médico, Declaração da Empresa ou Ementas. (a, d, i)",
        minAttachments: 1
    },
    "Matriz curricular": {
        descriptionMessage: "Confirme a solicitação da matriz curricular do curso.",
    },
    "Reabertura de Matrícula": {
        descriptionMessage: "Informe o motivo da solicitação de reabertura.",
    },
    "Reintegração ( ) Estágio ( ) Entrega do Relatório de Estágio ( ) TCC": {
        descriptionMessage: "Especifique se é reintegração para Estágio, Relatório ou TCC.",
    },
    "Reintegração para Cursar (Solicitar no Protocolo Geral)": {
        descriptionMessage: "Relate a necessidade de reintegração para cursar disciplinas.",
    },
    "Solicitação de Conselho de Classe": {
        descriptionMessage: "Descreva o motivo da solicitação ao Conselho de Classe.",
    },
    "Trancamento de Matrícula": {
        descriptionMessage: "Informe o motivo do trancamento de matrícula.",
    },
    "Transferência de Turno (especifique turno)": {
        descriptionMessage: "Especifique o turno para o qual deseja a transferência.",
        attachmentMessage: "📎 Anexe: Atestado Médico ou Declaração de Unidade Militar. (a/j)",
        minAttachments: 1
    }
};

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
        maxAttachments: 10,
    });

    const initialOptions = [
        { label: " Novo Requerimento", action: "start_flow", icon: <FilePlus2 size={16} /> },
        { label: " Meus Pedidos", action: "view_requests", icon: <ClipboardList size={18} /> },
    ];

    useEffect(() => {
        if (user) {
            setMessages([
                {
                    id: "init",
                    role: "bot",
                    text: (
                        <span>
                            Olá, <span className="text-[#15803d] font-semibold">{user.name.split(" ")[0]}</span>! Sou o assistente virtual. Como posso ajudar você hoje?
                        </span>
                    ),
                    options: initialOptions,
                },
            ]);
        }
    }, [user]);

    useEffect(() => {
        bottomRef.current?.scrollIntoView({ behavior: "smooth" });
    }, [messages, loading]);

    const cancelFlow = () => {
        setStep("idle");
        setFiles([]);
        setMessages((prev) => [
            ...prev,
            { id: Date.now(), role: "bot", text: "A operação foi cancelada. Posso ajudar em algo mais?", options: initialOptions },
        ]);
    };

    const handleFileSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files) {
            const selected = Array.from(e.target.files);
            setFiles((prev) => [...prev, ...selected]);
        }
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
            setTempData((prev) => ({ ...prev, description: "" }));
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

        if (tempData.minAttachments === 0) {
            finalizeRequest(text);
        } else {
            setStep("waiting_file");
            setMessages((prev) => [
                ...prev,
                {
                    id: Date.now() + 1,
                    role: "bot",
                    text: REQUEST_CONFIG[tempData.typeName]?.attachmentMessage || "Por favor, anexe os documentos necessários abaixo.",
                },
            ]);
        }
    };

    const handleAction = async (opt: any) => {
        if (opt.action === "start_flow") {
            const res = await fetch(`${API_BASE}/type-requests`);
            const data = await res.json();
            const allTypes = data.data || data || [];

            const pdfOptionNames = Object.keys(REQUEST_CONFIG);

            const filteredOptions = allTypes.filter((t: any) =>
                pdfOptionNames.includes(t.name)
            );

            setMessages((prev) => [
                ...prev,
                { id: Date.now(), role: "user", text: opt.label },
                {
                    id: Date.now() + 1,
                    role: "bot",
                    text: "Qual requerimento você deseja abrir?",
                    options: filteredOptions.map((t: any) => ({
                        label: t.name,
                        value: t.id,
                        action: "select_type",
                    })),
                },
            ]);
        } else if (opt.action === "select_type") {
            const config = REQUEST_CONFIG[opt.label];
            setTempData({
                typeId: opt.value,
                typeName: opt.label,
                description: "",
                minAttachments: config?.minAttachments ?? 0,
                maxAttachments: 10,
            });
            setStep("description");
            setMessages((prev) => [
                ...prev,
                { id: Date.now(), role: "user", text: opt.label },
                { id: Date.now() + 1, role: "bot", text: config?.descriptionMessage || "Por favor, descreva o motivo da sua solicitação:" },
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
        <div className="flex flex-col h-[100dvh] bg-white font-sans text-slate-700 selection:bg-green-100 antialiased font-normal">
            <main className="flex-1 overflow-y-auto px-4 py-8 space-y-8 scrollbar-hide max-w-4xl mx-auto w-full">
                {messages.map((msg) => (
                    <div key={msg.id} className={`flex flex-col ${msg.role === "user" ? "items-end" : "items-start"} w-full`}>
                        {msg.role === "bot" && (
                            <div className="flex items-center gap-2 mb-2 ml-1">
                                <div className="w-8 h-8 rounded-full border border-gray-100 flex items-center justify-center overflow-hidden bg-white shadow-sm">
                                    <img src="/jacareu.jpg" alt="Jacaréu" className="w-full h-full object-cover" />
                                </div>
                                <span className="text-xs font-semibold text-[#15803d] tracking-widest uppercase">Secretaria</span>
                            </div>
                        )}

                        <div className={`p-5 shadow-sm transition-all text-base leading-relaxed tracking-tight
                            ${msg.role === "user"
                                ? "bg-[#15803d] text-white rounded-3xl rounded-tr-none max-w-[85%]"
                                : "bg-[#f8fafc] border border-gray-100 text-slate-700 rounded-3xl rounded-tl-none max-w-[90%]"
                            }`}
                        >
                            {msg.text}

                            {msg.items && (
                                <div className="mt-4 space-y-3">
                                    {msg.items.map((req, i) => (
                                        <div key={i} className="flex justify-between items-center bg-white p-4 rounded-2xl border border-gray-50 shadow-sm hover:shadow-md transition-shadow">
                                            <span className="text-sm font-medium text-slate-800">{req.subject}</span>
                                            <span className={`text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full 
                                                ${req.status === "Concluído" ? "bg-green-100 text-green-700" :
                                                    req.status === "Em análise" ? "bg-amber-100 text-amber-700" :
                                                        "bg-slate-100 text-slate-500"}`}>
                                                {req.status}
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            )}

                            {msg.options && (
                                <div className="flex flex-wrap gap-2 mt-4">
                                    {msg.options.map((opt, i) => (
                                        <button
                                            key={i}
                                            onClick={() => handleAction(opt)}
                                            className="flex items-center gap-2 px-5 py-2.5 bg-white border border-gray-200 text-slate-600 text-sm rounded-full hover:border-[#15803d] hover:text-[#15803d] hover:shadow-md transition-all transform hover:-translate-y-0.5"
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

            <footer className="p-6 bg-white border-t border-gray-50">
                <div className="max-w-4xl mx-auto space-y-6">
                    <form onSubmit={handleSendMessage} className="flex gap-3 items-center">
                        {step !== "idle" && (
                            <button
                                type="button"
                                onClick={cancelFlow}
                                className="p-3 text-slate-400 hover:text-red-500 transition-colors"
                                title="Cancelar"
                            >
                                <XCircle size={24} />
                            </button>
                        )}

                        {step === "waiting_file" && (
                            <>
                                <input type="file" ref={fileInputRef} multiple onChange={handleFileSelect} className="hidden" />
                                <button
                                    type="button"
                                    onClick={() => fileInputRef.current?.click()}
                                    className="flex items-center gap-2 px-4 py-3 bg-[#f8fafc] text-[#15803d] rounded-2xl hover:bg-[#dcfce7] transition-all font-medium border border-gray-100 shadow-sm"
                                >
                                    <Paperclip size={20} />
                                    <span className="hidden sm:inline">Anexar</span>
                                </button>
                                {files.length >= tempData.minAttachments && (
                                    <button
                                        type="button"
                                        onClick={() => finalizeRequest(tempData.description)}
                                        className="flex items-center gap-2 px-6 py-3 bg-[#15803d] text-white rounded-2xl text-sm font-semibold hover:bg-[#166534] shadow-lg transition-all transform hover:-translate-y-0.5"
                                    >
                                        Enviar Pedido
                                    </button>
                                )}
                            </>
                        )}

                        <input
                            value={inputValue}
                            onChange={(e) => setInputValue(e.target.value)}
                            disabled={step !== "description"}
                            placeholder={step === "waiting_file" ? `${files.length} arquivo(s) prontos...` : "Escreva sua mensagem aqui..."}
                            className="flex-1 bg-[#f8fafc] border border-gray-100 rounded-2xl px-6 py-4 text-sm outline-none focus:ring-2 focus:ring-[#15803d]/10 focus:border-[#15803d] transition-all text-slate-600 placeholder:text-slate-300 shadow-inner"
                        />

                        <button
                            type="submit"
                            disabled={step !== "description" || !inputValue.trim()}
                            className="p-4 bg-[#15803d] text-white rounded-2xl hover:bg-[#166534] disabled:bg-slate-200 shadow-lg transition-all transform active:scale-95"
                        >
                            <Send size={20} />
                        </button>
                    </form>

                    <div className="flex flex-col sm:flex-row gap-4 justify-center items-center pt-2">
                        <button
                            onClick={() => handleAction({ action: "start_flow", label: " Novo Requerimento" })}
                            className="w-full sm:w-auto flex items-center justify-center gap-2 px-8 py-3.5 bg-[#15803d] text-white font-semibold rounded-full hover:bg-[#166534] transition-all shadow-md hover:shadow-lg transform hover:-translate-y-0.5"
                        >
                            <FilePlus2 size={20} /> Novo Requerimento
                        </button>
                        <button
                            onClick={() => handleAction({ action: "view_requests", label: " Meus Pedidos" })}
                            className="w-full sm:w-auto flex items-center justify-center gap-2 px-8 py-3.5 border border-[#15803d] text-[#15803d] font-semibold rounded-full hover:bg-[#f0fdf4] transition-all transform hover:-translate-y-0.5"
                        >
                            <ClipboardList size={20} /> Meus Pedidos
                        </button>
                    </div>
                </div>
            </footer>
        </div>
    );
}