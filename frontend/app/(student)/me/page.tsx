'use client';

import { useState, useRef, useEffect } from 'react';
import { useAuth } from '../../context/AuthContext';
import { Send, Paperclip, XCircle, Loader2, FilePlus2, ClipboardList } from 'lucide-react';

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
    text: | React.ReactNode;
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
        { label: ' Novo Requerimento', action: 'start_flow', icon: <FilePlus2 size={18}/> },
        { label: ' Meus Pedidos', action: 'view_requests', icon: <ClipboardList size={18}/> }
    ];

    useEffect(() => {
        if (user) {
            setMessages([{
                id: 'init',
                role: 'bot',
                text: (
  <span>
    Olá, <strong className="text-[#2e7d32]">{user.name.split(' ')[0]}</strong>! Sou o Jacaréu. Como posso ajudar você hoje?
  </span>
),

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

    const handleFileSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files) {
            const selected = Array.from(e.target.files);
            setFiles(prev => [...prev, ...selected]);
        }
    };

    const finalizeRequest = async (currentDescription?: string) => {
        if (loading) return;

        const finalDescription = currentDescription || tempData.description;
        if (!finalDescription.trim()) {
            console.error("Erro: descrição vazia");
            return;
        }

        setLoading(true);
        try {
            const documentIds: string[] = [];

            // Upload de arquivos
            if (files.length > 0) {
                for (const file of files) {
                    const formData = new FormData();
                    formData.append("arquivo", file);

                    const uploadRes = await fetch(`${API_BASE}/documents/upload`, {
                        method: "POST",
                        headers: { Authorization: `Bearer ${token}` },
                        body: formData,
                    });

                    if (!uploadRes.ok) throw new Error("Erro no upload de arquivo");

                    const uploadData = await uploadRes.json();
                    documentIds.push(uploadData.id);
                }
            }

            // Envio do requerimento
            const payload = {
                type_id: tempData.typeId,
                subject: tempData.typeName,
                description: finalDescription,
                document_ids: documentIds,
            };

            const res = await fetch(`${API_BASE}/requests`, {
                method: "POST",
                headers: {
                    Authorization: `Bearer ${token}`,
                    "Content-Type": "application/json",
                    Accept: "application/json",
                },
                body: JSON.stringify(payload),
            });

            if (!res.ok) {
                const errorDetail = await res.json();
                console.error("Erro detalhado:", errorDetail);
                throw new Error("Erro ao enviar requerimento");
            }

            setMessages(prev => [
                ...prev,
                { id: Date.now(), role: "bot", text: `✅ Requerimento de "${tempData.typeName}" enviado com sucesso!` },
                { id: Date.now() + 1, role: "bot", text: "Deseja realizar mais alguma operação?", options: initialOptions }
            ]);

            setStep("idle");
            setFiles([]);
            setTempData(prev => ({ ...prev, description: "" }));

        } catch (error) {
            setMessages(prev => [...prev, { id: Date.now(), role: "bot", text: "❌ Erro ao enviar. Tente novamente." }]);
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
            finalizeRequest(text);
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

      return (
  <div className="flex flex-col h-[100dvh] bg-[#f0f2f5] font-sans text-lg">
    <main className="flex-1 overflow-y-auto p-6 space-y-6">
      {messages.map((msg) => (
        <div key={msg.id} className={`flex ${msg.role === 'user' ? 'justify-end' : 'justify-start'} gap-3`}>
          {msg.role !== 'user' && (
            <div className="flex items-center gap-2 mb-1">
              <img
                src="/jacareu.jpg"
                alt="Jacaréu"
                className="w-10 h-10 rounded-full border-2 border-[#2e7d32]"
              />
              <span className="text-[#2e7d32] font-bold">Jacaréu</span>
            </div>
          )}

          <div
            className={`p-4 rounded-md max-w-[80%] ${
              msg.role === 'user'
                ? 'bg-blue-600 text-white'
                : 'bg-[#c8e6c9] text-[#2e7d32]'
            }`}
          >
            {msg.text}

            {msg.options && (
              <div className="flex gap-3 mt-3 flex-wrap">
                {msg.options.map((opt, i) => (
                  <button
                    key={i}
                    onClick={() => handleAction(opt)}
                    className="flex items-center gap-2 px-4 py-2 bg-[#66bb6a] text-white text-sm rounded-md hover:bg-[#81c784]"
                  >
                    {opt.icon} {opt.label}
                  </button>
                ))}
              </div>
            )}
          </div>
        </div>
      ))}

      {files.length > 0 && (
        <div className="text-xs text-gray-500 italic">📎 {files.length} arquivo(s) selecionado(s)</div>
      )}

      {loading && (
        <div className="flex justify-center">
          <Loader2 className="animate-spin text-[#2e7d32]" />
        </div>
      )}

      <div ref={bottomRef} />
    </main>

    <footer className="p-4 bg-white">
      <form onSubmit={handleSendMessage} className="flex gap-3 items-center max-w-4xl mx-auto">
        {step !== 'idle' && (
          <button
            type="button"
            onClick={cancelFlow}
            className="flex items-center gap-2 px-3 py-2 text-red-600 hover:text-red-700 font-bold"
          >
            <XCircle size={22} />
            Cancelar
          </button>
        )}

        {step === 'waiting_file' && (
          <>
            <input type="file" ref={fileInputRef} multiple onChange={handleFileSelect} className="hidden" />
            <button
              type="button"
              onClick={() => fileInputRef.current?.click()}
              className="flex items-center gap-2 px-3 py-2 text-[#2e7d32] hover:text-[#1b5e20] font-bold"
            >
              <Paperclip size={22} />
              Anexar
            </button>

            {files.length >= tempData.minAttachments && (
              <button
                type="button"
                onClick={() => finalizeRequest(tempData.description)}
                className="flex items-center gap-2 px-5 py-2 bg-[#2e7d32] text-white rounded-md text-sm font-bold hover:bg-[#1b5e20]"
              >
                Enviar {files.length} anexo(s)
              </button>
            )}
          </>
        )}

        <input
          value={inputValue}
          onChange={(e) => setInputValue(e.target.value)}
          disabled={step !== 'description'}
          placeholder={step === 'waiting_file' ? 'Anexe os arquivos...' : 'Escreva aqui...'}
          className="flex-1 border border-[#c8e6c9] rounded-md px-4 py-3 text-sm focus:ring-2 focus:ring-[#66bb6a] outline-none"
        />

        <button
          type="submit"
          disabled={step !== 'description' || !inputValue.trim()}
          className="flex items-center gap-2 p-3 bg-[#2e7d32] text-white rounded-md hover:bg-[#1b5e20] disabled:bg-gray-300 font-bold"
        >
          <Send size={22} />
          Enviar
        </button>
      </form>

      {/* Botões extras abaixo */}
      <div className="flex gap-4 mt-6 justify-center">
        <button className="flex items-center gap-2 px-6 py-3 bg-[#66bb6a] text-white font-bold rounded-md hover:bg-[#81c784]">
          <FilePlus2 size={20} /> Novo Requerimento
        </button>
        <button className="flex items-center gap-2 px-6 py-3 bg-[#66bb6a] text-white font-bold rounded-md hover:bg-[#81c784]">
          <ClipboardList size={20} /> Meus Pedidos
        </button>
      </div>
    </footer>
  </div>
);
}