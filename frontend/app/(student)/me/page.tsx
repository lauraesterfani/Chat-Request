'use client';

import { useState, useRef, useEffect } from 'react';
import { useAuth } from '../../context/AuthContext';
import { FileText, Send, Paperclip, X, CheckCircle } from 'lucide-react';

const API_BASE = "http://127.0.0.1:8000/api";

const MANDATORY_ATTACHMENT_KEYWORDS = [
    "aproveitamento", "isenção", "justificativa", "falta", 
    "chamada", "educação física", "transferência", "cancelamento"
];

interface Message {
    id: string | number;
    role: 'bot' | 'user';
    text: string;
    options?: any[];
    requestsData?: any[];
}

export default function GuidedChatPage() {
    const { token, user } = useAuth();
    const bottomRef = useRef<HTMLDivElement>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);
    
    const [messages, setMessages] = useState<Message[]>([]);
    const [loading, setLoading] = useState(false);
    const [step, setStep] = useState<'idle' | 'subject' | 'description' | 'attachment_choice' | 'waiting_file'>('idle');
    const [tempData, setTempData] = useState({ typeId: "", subject: "", description: "", typeName: "", isMandatory: false });
    const [inputValue, setInputValue] = useState("");

    const initialOptions = [
        { label: '📝 Novo Requerimento', action: 'start_flow' },
        { label: '📂 Meus Pedidos', action: 'view_requests' }
    ];

    useEffect(() => {
        if (user) {
            setMessages([{
                id: 'init', role: 'bot',
                text: `Olá, ${user.name.split(' ')[0]}! Sou a Stella. Como posso ajudar você hoje?`,
                options: initialOptions
            }]);
        }
    }, [user]);

    // Scroll suave sempre para a última mensagem
    useEffect(() => {
        bottomRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages, loading]);

    const finalizeRequest = async (fileToUpload: File | null = null) => {
        setLoading(true);
        try {
            let documentIds: string[] = [];
            if (fileToUpload) {
                const formData = new FormData();
                formData.append("arquivo", fileToUpload);
                const uploadRes = await fetch(`${API_BASE}/documents/upload`, {
                    method: "POST", headers: { Authorization: `Bearer ${token}` },
                    body: formData,
                });
                const uploadData = await uploadRes.json();
                if (uploadRes.ok) documentIds = [uploadData.id];
            }

            const res = await fetch(`${API_BASE}/requests`, {
                method: "POST",
                headers: { Authorization: `Bearer ${token}`, "Content-Type": "application/json" },
                body: JSON.stringify({
                    type_id: tempData.typeId,
                    subject: tempData.subject,
                    description: tempData.description,
                    document_ids: documentIds
                }),
            });

            if (res.ok) {
                setMessages(prev => [...prev, { 
                    id: Date.now(), role: 'bot', 
                    text: `Tudo pronto! Seu requerimento de "${tempData.typeName}" foi enviado com sucesso. ✅` 
                }]);

                setTimeout(() => {
                    setMessages(prev => [...prev, {
                        id: Date.now() + 500, role: 'bot',
                        text: 'Deseja realizar mais alguma operação?',
                        options: initialOptions
                    }]);
                }, 1000);
            }
        } catch (err) {
            setMessages(prev => [...prev, { id: Date.now(), role: 'bot', text: "Erro no envio. Tente novamente." }]);
        }
        setStep('idle');
        setLoading(false);
        setTempData({ typeId: "", subject: "", description: "", typeName: "", isMandatory: false });
    };

    const handleSendMessage = (e?: React.FormEvent) => {
        if (e) e.preventDefault();
        if (!inputValue.trim()) return;

        const text = inputValue;
        setInputValue("");
        setMessages(prev => [...prev, { id: Date.now(), role: 'user', text }]);

        if (step === 'subject') {
            setTempData(prev => ({ ...prev, subject: text }));
            setTimeout(() => {
                setMessages(prev => [...prev, { id: Date.now() + 1, role: 'bot', text: "Entendido. Agora, descreva brevemente o motivo do seu pedido:" }]);
                setStep('description');
            }, 600);
        } 
        else if (step === 'description') {
            setTempData(prev => ({ ...prev, description: text }));
            setTimeout(() => {
                if (tempData.isMandatory) {
                    setMessages(prev => [...prev, { id: Date.now() + 1, role: 'bot', text: "Este pedido exige anexo. Clique no clipe 📎 para enviar o documento:" }]);
                    setStep('waiting_file');
                } else {
                    setMessages(prev => [...prev, { 
                        id: Date.now() + 1, role: 'bot', 
                        text: "Deseja anexar algum comprovante opcional?",
                        options: [{ label: '📎 Sim, anexar', action: 'ask_file' }, { label: '⏩ Não, enviar agora', action: 'submit_no_file' }]
                    }]);
                    setStep('attachment_choice');
                }
            }, 600);
        }
    };

    const handleAction = async (opt: any) => {
        if (opt.action === 'start_flow') {
            const res = await fetch(`${API_BASE}/type-requests`);
            const data = await res.json();
            setMessages(prev => [...prev, { id: Date.now(), role: 'user', text: opt.label }, { 
                id: Date.now() + 1, role: 'bot', text: "Qual destes requerimentos você deseja abrir?", 
                options: (data.data || data).map((t: any) => ({ label: t.name, value: t.id, action: 'select_type' })) 
            }]);
        }
        else if (opt.action === 'select_type') {
            const isMandatory = MANDATORY_ATTACHMENT_KEYWORDS.some(kw => opt.label.toLowerCase().includes(kw));
            setTempData({ ...tempData, typeId: opt.value, typeName: opt.label, isMandatory });
            setMessages(prev => [...prev, { id: Date.now(), role: 'user', text: opt.label }, { id: Date.now() + 1, role: 'bot', text: "Qual o ASSUNTO deste requerimento?" }]);
            setStep('subject');
        }
        else if (opt.action === 'ask_file') {
            setMessages(prev => [...prev, { id: Date.now(), role: 'bot', text: "Selecione o arquivo clicando no ícone de clipe 📎 abaixo:" }]);
            setStep('waiting_file');
        }
        else if (opt.action === 'submit_no_file') {
            finalizeRequest(null);
        }
        else if (opt.action === 'view_requests') {
            const res = await fetch(`${API_BASE}/requests`, { headers: { Authorization: `Bearer ${token}` } });
            const data = await res.json();
            setMessages(prev => [...prev, { id: Date.now(), role: 'user', text: opt.label }, { id: Date.now() + 1, role: 'bot', text: "Seus pedidos recentes:", requestsData: (data.data || data).slice(0, 5) }]);
        }
    };

    return (
        /* h-[100dvh] garante que o app ocupe exatamente a tela visível, descontando barras do navegador */
        <div className="flex flex-col h-[100dvh] bg-[#f0f2f5] font-sans antialiased">
            
            <header className="bg-white border-b border-gray-200 px-4 py-4 shadow-sm shrink-0">
                <div className="max-w-3xl mx-auto flex items-center gap-3">
                    <div className="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center text-green-600 font-black shadow-sm shrink-0">CR</div>
                    <div className="overflow-hidden">
                        <h1 className="text-base sm:text-lg font-black text-slate-800 leading-tight tracking-tighter truncate">Secretaria Virtual</h1>
                        <p className="text-[10px] sm:text-xs text-green-600 font-bold uppercase tracking-widest">Stella Online</p>
                    </div>
                </div>
            </header>

            {/* A área de mensagens agora é flex-1, ocupando todo o espaço entre o topo e o rodapé */}
            <main className="flex-1 overflow-y-auto px-3 py-6 sm:px-6">
                <div className="max-w-3xl mx-auto space-y-6">
                    {messages.map((msg) => (
                        <div key={msg.id} className={`flex w-full ${msg.role === 'user' ? 'justify-end' : 'justify-start'}`}>
                            <div className={`flex max-w-[92%] sm:max-w-[80%] gap-2 sm:gap-3 ${msg.role === 'user' ? 'flex-row-reverse' : 'flex-row'}`}>
                                <div className={`w-7 h-7 sm:w-8 sm:h-8 rounded-full flex-shrink-0 flex items-center justify-center text-[10px] font-bold mt-auto mb-1 ${msg.role === 'user' ? 'bg-indigo-100 text-indigo-600' : 'bg-white text-green-600 border border-gray-100 shadow-sm'}`}>
                                    {msg.role === 'user' ? 'EU' : 'CR'}
                                </div>
                                <div className="flex flex-col gap-2">
                                    <div className={`px-4 py-3 sm:px-5 sm:py-3.5 text-sm sm:text-[15px] shadow-sm tracking-tight font-medium ${msg.role === 'user' ? 'bg-[#15803d] text-white rounded-2xl rounded-br-none' : 'bg-white border border-gray-100 text-slate-700 rounded-2xl rounded-bl-none'}`}>
                                        {msg.text}
                                    </div>
                                    {msg.requestsData && (
                                        <div className="space-y-2 mt-1">
                                            {msg.requestsData.map((req: any) => (
                                                <div key={req.id} className="bg-white border border-gray-100 p-3 rounded-xl flex items-center justify-between shadow-sm gap-2">
                                                    <div className="flex items-center gap-2 overflow-hidden">
                                                        <FileText size={14} className="text-gray-400 shrink-0" />
                                                        <span className="text-[11px] font-black text-gray-700 truncate tracking-tight">{req.subject}</span>
                                                    </div>
                                                    <span className="text-[9px] font-black px-2 py-0.5 bg-amber-50 text-amber-600 rounded-full shrink-0 border border-amber-100 uppercase tracking-tighter">{req.status}</span>
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                    {msg.role === 'bot' && msg.options && (
                                        <div className="flex flex-wrap gap-2 mt-1">
                                            {msg.options.map((opt, idx) => (
                                                <button key={idx} onClick={() => handleAction(opt)} className="bg-white border border-green-200 text-[#15803d] px-3 py-2 sm:px-4 sm:py-2.5 rounded-xl text-xs sm:text-sm font-black shadow-sm hover:bg-green-50 active:scale-95 transition-all tracking-tight">
                                                    {opt.label}
                                                </button>
                                            ))}
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    ))}
                    {/* Elemento invisível para scroll */}
                    <div ref={bottomRef} className="h-2" />
                </div>
            </main>

            {/* Rodapé fixo na base da flexbox - Quando o teclado sobe, ele sobe junto */}
            <footer className="bg-white border-t border-gray-200 p-3 sm:p-4 shrink-0">
                <form onSubmit={handleSendMessage} className="max-w-3xl mx-auto flex gap-2 items-center">
                    {(step === 'waiting_file' || step === 'attachment_choice') && (
                        <>
                            <input type="file" ref={fileInputRef} onChange={(e) => finalizeRequest(e.target.files?.[0] || null)} className="hidden" />
                            <button type="button" onClick={() => fileInputRef.current?.click()} className="p-2.5 sm:p-3 bg-gray-50 text-[#15803d] rounded-full border border-green-200 hover:bg-green-100 shadow-sm transition-colors">
                                <Paperclip size={18} />
                            </button>
                        </>
                    )}
                    <input 
                        type="text" value={inputValue} onChange={(e) => setInputValue(e.target.value)}
                        placeholder={step === 'idle' ? "Stella está aguardando..." : "Digite sua mensagem..."}
                        disabled={step === 'idle' || step === 'waiting_file' || step === 'attachment_choice'}
                        className="flex-1 px-4 py-2.5 sm:px-5 sm:py-3 rounded-full border border-gray-200 text-xs sm:text-sm outline-none focus:border-green-500 bg-white shadow-inner font-medium"
                    />
                    <button type="submit" disabled={!inputValue.trim()} className="p-2.5 sm:p-3 bg-[#15803d] text-white rounded-full shadow-lg active:scale-95 disabled:bg-gray-200 transition-all">
                        <Send size={18} />
                    </button>
                </form>
                <p className="text-center text-[8px] text-gray-400 mt-2 uppercase tracking-[0.2em] font-black">Ambiente Seguro • ChatRequest</p>
            </footer>
        </div>
    );
}