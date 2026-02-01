'use client';

import { useState, useRef, useEffect } from 'react';
import { useAuth } from '../../context/AuthContext';
import { Send, Paperclip, XCircle, Loader2 } from 'lucide-react';

const API_BASE = "http://127.0.0.1:8000/api";

const REQUEST_CONFIG: Record<string, {
    descriptionMessage: string;
    attachmentMessage?: string;
    minAttachments?: number;
    maxAttachments?: number;
}> = {
    "c79f4dd0-8a1c-4ec3-ae1c-a9c3c24a3a13": {
        descriptionMessage: "Descreva o motivo da justificativa de falta.",
        attachmentMessage: "📎 Anexe o(s) atestado(s) médico(s) ou declaração.",
        minAttachments: 1,
        maxAttachments: 10
    },
    "ffcd16ff-2268-4a59-84c7-c083854a5541": {
        descriptionMessage: "Explique o motivo da solicitação de Trancamento de Matricula.",
    },
    "49e153cc-f9d0-43e3-ad41-70c618c2f23f": {
        descriptionMessage: "Explique o motivo da solicitação de Ajuste de Matrícula Semestral.",
    },
    "55307bb1-a5ef-4db2-99ca-0e57124655a8": {
        descriptionMessage: "Explique o motivo da solicitação de Autorização para cursar em outra IES.",
    },
    "abb3412c-5089-4103-b99e-aace5a2dcfcf": {
        descriptionMessage: "Explique o motivo da solicitação de Cancelamento de Matrícula.",
    },
    "f004abae-8f69-463e-bce1-973119fedcf8": {
        descriptionMessage: "Explique o motivo da solicitação da Declaração de Matrícula / Vínculo",
    },
    "abcfa491-e9d5-411d-97d9-00ec82646ee3": {
        descriptionMessage: "Explique o motivo da solicitação.",
        attachmentMessage: "📎 Para a transferência, por favor anexe os documentos necessários (atestado médico ou Declaração de Unidade Militar).",
        minAttachments: 1,
        maxAttachments: 10
    },
    "3854d826-5b4f-4df7-8004-463c941a1bc5": {
        descriptionMessage: "Digite o ano e o semestre para Diploma / Certificado de Conclusão. Ex: 2019.1",
    },
    "f00a8cf5-80e6-405d-86ce-22aa3ab3d0c0": { 
        descriptionMessage: "Explique o motivo da solicitação.",
        attachmentMessage: "📎 Para a Dispensa Prática, por favor anexe os documentos necessários (atestado médico ou Declaração de Unidade Militar).",
        minAttachments: 1,
        maxAttachments: 10
    },
    "8fcb51b8-ac47-4bd5-acbf-2b34dc4ca64f": {
        descriptionMessage: "Especifique qual cadeira deseja para a Ementa de Disciplina. Ex: Cálculo I",
    },
    "92ac7b65-a291-4d31-a78b-d7d963a28f6d": {
        descriptionMessage: "Explique o motivo da solicitação da Guia de Transferência.",
    },
    "83d615b3-a13c-4fc9-ab25-0fabfb1a5e5ce": {
        descriptionMessage: "Digite o ano e o semestre para o Histórico Escolar. Ex: 2019.1",
    },
    "60f403cb-a100-4619-b7e8-bb1180897418": {
        descriptionMessage: "Explique o motivo da solicitação.",
        attachmentMessage: "📎 Para Isenção de Disciplinas, anexe Histórico Escolar (Original) e Ementas das disciplinas cursadas.",
        minAttachments: 1,
        maxAttachments: 10
    },
    "f09e9bbf-a4fb-48af-a7de-34a28bc34211": {
        descriptionMessage: "Explique o motivo da solicitação da Reabertura de Matrícula.",
    },
    "de20efff-e610-48be-b9aa-c45da0ca59fa": {
        descriptionMessage: "Explique o motivo da solicitação da Revisão de Nota ou Faltas.",
    },
};

interface Message {
    id: string | number;
    role: 'bot' | 'user';
    text: string;
    options?: any[];
}

export default function GuidedChatPage() {
    const { token, user } = useAuth();
    const bottomRef = useRef<HTMLDivElement>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const [messages, setMessages] = useState<Message[]>([]);
    const [loading, setLoading] = useState(false);
    const [step, setStep] = useState<'idle' | 'description' | 'waiting_file'>('idle');
    const [inputValue, setInputValue] = useState("");
    const [files, setFiles] = useState<File[]>([]);
    const [tempData, setTempData] = useState({
        typeId: "",
        typeName: "",
        description: "",
        minAttachments: 0,
        maxAttachments: 10
    });

    const initialOptions = [
        { label: '📝 Novo Requerimento', action: 'start_flow' },
        { label: '📂 Meus Pedidos', action: 'view_requests' }
    ];

    useEffect(() => {
        if (user) {
            setMessages([{
                id: 'init',
                role: 'bot',
                text: `Olá, ${user.name.split(' ')[0]}! Sou a Stella. Como posso ajudar você hoje?`,
                options: initialOptions
            }]);
        }
    }, [user]);

    useEffect(() => {
        bottomRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages, loading]);

    const cancelFlow = () => {
        setStep('idle');
        setFiles([]);
        setMessages(prev => [...prev, {
            id: Date.now(),
            role: 'bot',
            text: "❌ Operação cancelada.",
            options: initialOptions
        }]);
    };

    const finalizeRequest = async () => {
        if (loading) return;
        setLoading(true);

        try {
            const documentIds: string[] = [];

            // 1. Upload dos arquivos
            for (const file of files) {
                const formData = new FormData();
                formData.append("arquivo", file);
                const uploadRes = await fetch(`${API_BASE}/documents/upload`, {
                    method: "POST",
                    headers: { Authorization: `Bearer ${token}` },
                    body: formData,
                });
                if (!uploadRes.ok) throw new Error("Falha no upload");
                const uploadData = await uploadRes.json();
                documentIds.push(uploadData.id);
            }

            // 2. Criação do Requerimento (Corrigido com SUBJECT)
            const res = await fetch(`${API_BASE}/requests`, {
                method: "POST",
                headers: {
                    Authorization: `Bearer ${token}`,
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    type_id: tempData.typeId,
                    subject: tempData.typeName, // O subject que o back exige
                    description: tempData.description,
                    document_ids: documentIds
                }),
            });

            if (!res.ok) throw new Error("Erro ao salvar requerimento");

            setMessages(prev => [
                ...prev,
                { id: Date.now(), role: 'bot', text: `✅ Requerimento enviado com sucesso!` },
                { id: Date.now() + 1, role: 'bot', text: "Mais alguma coisa?", options: initialOptions }
            ]);
            setStep('idle');
            setFiles([]);
        } catch (error) {
            setMessages(prev => [...prev, { id: Date.now(), role: 'bot', text: "❌ Erro ao enviar. Verifique sua conexão ou os anexos." }]);
        } finally {
            setLoading(false);
        }
    };

    const handleSendMessage = (e?: React.FormEvent) => {
        if (e) e.preventDefault();
        if (!inputValue.trim() || step !== 'description') return;

        const text = inputValue;
        setInputValue("");
        setMessages(prev => [...prev, { id: Date.now(), role: 'user', text }]);
        setTempData(prev => ({ ...prev, description: text }));

        if (tempData.minAttachments === 0) {
            setStep('idle');
            setTimeout(() => finalizeRequest(), 500);
        } else {
            setStep('waiting_file');
            setMessages(prev => [...prev, {
                id: Date.now() + 1,
                role: 'bot',
                text: REQUEST_CONFIG[tempData.typeId]?.attachmentMessage || "📎 Por favor, anexe os documentos."
            }]);
        }
    };

    const handleAction = async (opt: any) => {
        if (opt.action === 'start_flow') {
            const res = await fetch(`${API_BASE}/type-requests`);
            const data = await res.json();
            setMessages(prev => [...prev, 
                { id: Date.now(), role: 'user', text: opt.label },
                { id: Date.now() + 1, role: 'bot', text: "Qual requerimento você deseja abrir?", 
                  options: (data.data || data).map((t: any) => ({ label: t.name, value: t.id, action: 'select_type' })) 
                }
            ]);
        } else if (opt.action === 'select_type') {
            const config = REQUEST_CONFIG[opt.value];
            setTempData({
                typeId: opt.value,
                typeName: opt.label,
                description: "",
                minAttachments: config?.minAttachments ?? 0,
                maxAttachments: config?.maxAttachments ?? 10
            });
            setStep('description');
            setMessages(prev => [...prev, 
                { id: Date.now(), role: 'user', text: opt.label },
                { id: Date.now() + 1, role: 'bot', text: config?.descriptionMessage || "Descreva o motivo:" }
            ]);
        }
    };

    const handleFileSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files) {
            const selected = Array.from(e.target.files);
            setFiles(prev => [...prev, ...selected]);
        }
    };

    return (
        <div className="flex flex-col h-[100dvh] bg-[#f0f2f5]">
            <main className="flex-1 overflow-y-auto p-4 space-y-4">
                {messages.map(msg => (
                    <div key={msg.id} className={`flex ${msg.role === 'user' ? 'justify-end' : 'justify-start'}`}>
                        <div className={`p-3 rounded-xl max-w-[80%] ${msg.role === 'user' ? 'bg-blue-600 text-white' : 'bg-white border shadow-sm'}`}>
                            {msg.text}
                            {msg.options && (
                                <div className="flex gap-2 mt-2 flex-wrap">
                                    {msg.options.map((opt, i) => (
                                        <button key={i} onClick={() => handleAction(opt)} className="px-3 py-1 bg-gray-100 text-gray-700 text-xs rounded-full border hover:bg-gray-200">
                                            {opt.label}
                                        </button>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>
                ))}
                {files.length > 0 && (
                    <div className="text-xs text-gray-500 italic">
                        📎 {files.length} arquivo(s) selecionado(s)
                    </div>
                )}
                {loading && <div className="flex justify-center"><Loader2 className="animate-spin text-blue-500" /></div>}
                <div ref={bottomRef} />
            </main>

            <footer className="p-4 bg-white border-t">
                <form onSubmit={handleSendMessage} className="flex gap-2 items-center max-w-4xl mx-auto">
                    {step !== 'idle' && (
                        <button type="button" onClick={cancelFlow} className="p-2 text-red-500"><XCircle /></button>
                    )}

                    {step === 'waiting_file' && (
                        <>
                            <input type="file" ref={fileInputRef} multiple onChange={handleFileSelect} className="hidden" />
                            <button type="button" onClick={() => fileInputRef.current?.click()} className="p-2 text-blue-600">
                                <Paperclip />
                            </button>
                            {files.length >= tempData.minAttachments && (
                                <button type="button" onClick={finalizeRequest} className="bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-bold">
                                    Enviar {files.length} anexo(s)
                                </button>
                            )}
                        </>
                    )}

                    <input
                        value={inputValue}
                        onChange={(e) => setInputValue(e.target.value)}
                        disabled={step !== 'description'}
                        placeholder={step === 'waiting_file' ? "Anexe os arquivos..." : "Escreva aqui..."}
                        className="flex-1 border rounded-full px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none"
                    />

                    <button type="submit" disabled={step !== 'description' || !inputValue.trim()} className="p-2 bg-blue-600 text-white rounded-full disabled:bg-gray-300">
                        <Send size={20} />
                    </button>
                </form>
            </footer>
        </div>
    );
}