'use client';

import { useState, useRef, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '../../context/AuthContext';
// Importando ícones para o Tutorial
import { HelpCircle, X, MessageSquare, FileText, CheckCircle } from 'lucide-react';

interface Option {
    label: string;
    value: string;
    action: 'request_type' | 'navigate' | 'initial_choice'; 
}

interface Message {
    id: string | number;
    role: 'bot' | 'user';
    text: string;
    options?: Option[]; 
}

export default function ChatDashboardPage() {
    const { token, user } = useAuth(); 
    const router = useRouter();
    const bottomRef = useRef<HTMLDivElement>(null);

    // Estados do Chat
    const [messages, setMessages] = useState<Message[]>([
        {
            id: 'init-1',
            role: 'bot',
            text: 'Olá! Sou o assistente virtual da secretaria. Carregando suas opções...',
            options: [] 
        }
    ]);
    const [loading, setLoading] = useState(false);

    // ESTADO DO TUTORIAL (NOVO)
    const [isTutorialOpen, setIsTutorialOpen] = useState(false);

    useEffect(() => {
        if (user) {
            setMessages(prev => {
                const newMsgs = [...prev];
                newMsgs[0] = {
                    id: 'init-1',
                    role: 'bot',
                    text: `Olá, ${user.name.split(' ')[0]}! Sou o assistente virtual da secretaria. Como posso ajudar você hoje?`,
                    options: [
                        { label: '📝 Solicitar Novo Requerimento', value: 'request', action: 'initial_choice' },
                        { label: '📂 Consultar Meus Pedidos', value: '/dashboard', action: 'navigate' }
                    ]
                };
                return newMsgs;
            });
        }
    }, [user]);

    useEffect(() => {
        bottomRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages, loading]);

    const handleOptionClick = async (option: Option) => {
        const userMsg: Message = { id: Date.now(), role: 'user', text: option.label.replace(/^[^\w]+/, '') }; 
        setMessages(prev => [...prev, userMsg]);
        setLoading(true);

        if (option.action === 'navigate') {
            setTimeout(() => router.push(option.value), 800);
            return;
        }

        if (option.value === 'request') {
            try {
                const response = await fetch('http://127.0.0.1:8000/api/type-requests', {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                if (!response.ok) throw new Error('Erro na API');
                const data = await response.json();
                const types = data.data || data;

                const typeOptions: Option[] = types.map((t: any) => ({
                    label: t.name,
                    value: t.id,
                    action: 'request_type'
                }));

                setTimeout(() => {
                    const botMsg: Message = {
                        id: Date.now() + 1,
                        role: 'bot',
                        text: 'Entendido. Selecione abaixo qual tipo de documento ou solicitação você precisa:',
                        options: typeOptions
                    };
                    setMessages(prev => [...prev, botMsg]);
                    setLoading(false);
                }, 600);
            } catch (error) {
                setLoading(false);
                setMessages(prev => [...prev, { id: Date.now(), role: 'bot', text: 'Desculpe, não consegui carregar as opções agora.' }]);
            }
        } 
        else if (option.action === 'request_type') {
            setTimeout(() => {
                setMessages(prev => [...prev, {
                    id: Date.now() + 1,
                    role: 'bot',
                    text: `Perfeito! Estou te redirecionando para o formulário de "${option.label}".`
                }]);
                setLoading(false);

                setTimeout(() => {
                    localStorage.setItem('selected_type_id', option.value); 
                    router.push('/requests/new'); 
                }, 1500);
            }, 500);
        }
    };

    return (
        <div className="min-h-screen bg-[#f0f2f5] flex flex-col font-sans">
            
            {/* Header Original */}
            <header className="sticky top-0 z-20 bg-white/90 backdrop-blur-sm border-b border-gray-200 px-6 py-4 shadow-sm">
                <div className="max-w-3xl mx-auto flex items-center gap-3">
                    <div className="relative">
                        <div className="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center text-green-600 font-bold">CR</div>
                        <span className="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white rounded-full"></span>
                    </div>
                    <div>
                        <h1 className="text-lg font-bold text-slate-800 leading-tight">Secretaria Virtual</h1>
                        <p className="text-xs text-green-600 font-medium">Online agora</p>
                    </div>
                </div>
            </header>

            <main className="flex-1 w-full max-w-3xl mx-auto p-4 sm:p-6 pb-24 overflow-y-auto">
                <div className="space-y-6">
                    {messages.map((msg) => (
                        <div key={msg.id} className={`flex w-full ${msg.role === 'user' ? 'justify-end' : 'justify-start'}`}>
                            <div className={`flex max-w-[90%] sm:max-w-[75%] gap-3 ${msg.role === 'user' ? 'flex-row-reverse' : 'flex-row'}`}>
                                <div className={`w-8 h-8 rounded-full flex-shrink-0 flex items-center justify-center text-xs font-bold shadow-sm mt-auto mb-1
                                    ${msg.role === 'user' ? 'bg-indigo-100 text-indigo-600' : 'bg-white text-green-600 border border-gray-200'}`}>
                                    {msg.role === 'user' ? 'EU' : 'CR'}
                                </div>
                                <div className="flex flex-col gap-2">
                                    <div className={`px-5 py-3.5 text-[15px] leading-relaxed shadow-sm transition-all duration-300
                                        ${msg.role === 'user' ? 'bg-[#15803d] text-white rounded-2xl rounded-br-none' : 'bg-white border border-gray-100 text-slate-700 rounded-2xl rounded-bl-none'}`}>
                                        {msg.text}
                                    </div>
                                    {msg.role === 'bot' && msg.options && msg.options.length > 0 && (
                                        <div className="flex flex-wrap gap-2 mt-1">
                                            {msg.options.map((opt, idx) => (
                                                <button key={idx} onClick={() => handleOptionClick(opt)} className="bg-white border border-green-200 text-[#15803d] px-4 py-2.5 rounded-xl text-sm font-semibold shadow-sm hover:shadow-md hover:bg-green-50 active:scale-95 transition-all">
                                                    {opt.label}
                                                </button>
                                            ))}
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    ))}
                    {loading && (
                        <div className="flex w-full justify-start">
                            <div className="flex gap-3 max-w-[85%]">
                                <div className="w-8 h-8 bg-white border border-gray-200 rounded-full flex items-center justify-center text-xs font-bold text-green-600 shadow-sm mt-auto mb-1">CR</div>
                                <div className="bg-white border border-gray-100 px-4 py-4 rounded-2xl rounded-bl-none shadow-sm flex items-center gap-1">
                                    <div className="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                                    <div className="w-2 h-2 bg-gray-400 rounded-full animate-bounce delay-100"></div>
                                    <div className="w-2 h-2 bg-gray-400 rounded-full animate-bounce delay-200"></div>
                                </div>
                            </div>
                        </div>
                    )}
                    <div ref={bottomRef} className="h-4" />
                </div>
            </main>

            {/* Input Estático Original */}
            <div className="sticky bottom-0 bg-white border-t border-gray-200 p-4">
                <div className="max-w-3xl mx-auto relative opacity-50 cursor-not-allowed">
                    <input type="text" placeholder="Selecione uma opção acima..." disabled className="w-full bg-gray-100 border-0 rounded-full py-3.5 px-5 text-gray-500 outline-none" />
                </div>
                <p className="text-center text-[10px] text-gray-400 mt-2 uppercase tracking-wider">Ambiente Seguro • ChatRequest v1.0</p>
            </div>

            {/* --- COMPONENTE DE TUTORIAL (ADICIONADO) --- */}
            
            {/* Botão de Ajuda Flutuante */}
            <button
                onClick={() => setIsTutorialOpen(true)}
                className="fixed bottom-20 right-6 w-12 h-12 bg-[#108542] text-white rounded-full shadow-lg hover:scale-110 active:scale-95 transition-all flex items-center justify-center z-50 group"
                title="Ajuda"
            >
                <HelpCircle size={24} />
            </button>

            {/* Modal de Tutorial */}
            {isTutorialOpen && (
                <div className="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
                    <div className="bg-white w-full max-w-sm rounded-3xl shadow-2xl overflow-hidden">
                        <div className="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                            <h3 className="font-black text-gray-800">Como usar o ChatRequest?</h3>
                            <button onClick={() => setIsTutorialOpen(false)} className="text-gray-400 hover:text-gray-600">
                                <X size={20} />
                            </button>
                        </div>
                        <div className="p-6 space-y-6">
                            <div className="flex gap-3">
                                <div className="p-2 bg-green-50 rounded-lg text-green-600 h-fit"><MessageSquare size={20}/></div>
                                <div><p className="font-bold text-sm">Passo 1</p><p className="text-xs text-gray-500">Inicie o chat clicando em "Solicitar Novo Requerimento".</p></div>
                            </div>
                            <div className="flex gap-3">
                                <div className="p-2 bg-green-50 rounded-lg text-green-600 h-fit"><FileText size={20}/></div>
                                <div><p className="font-bold text-sm">Passo 2</p><p className="text-xs text-gray-500">Escolha o serviço desejado. Stella irá te redirecionar para o formulário.</p></div>
                            </div>
                            <div className="flex gap-3">
                                <div className="p-2 bg-green-50 rounded-lg text-green-600 h-fit"><CheckCircle size={20}/></div>
                                <div><p className="font-bold text-sm">Passo 3</p><p className="text-xs text-gray-500">Preencha os campos e anexe arquivos se necessário. Depois é só enviar!</p></div>
                            </div>
                            <button onClick={() => setIsTutorialOpen(false)} className="w-full bg-[#108542] text-white font-bold py-3 rounded-xl mt-4">Entendi!</button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}