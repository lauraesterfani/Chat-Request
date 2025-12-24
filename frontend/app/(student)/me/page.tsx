'use client';

import { useState, useRef, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '../../context/AuthContext'; // Caminho relativo corrigido

// --- Tipos ---
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

    // Estado inicial com ID fixo para evitar erro de Hidratação
    const [messages, setMessages] = useState<Message[]>([
        {
            id: 'init-1',
            role: 'bot',
            text: 'Olá! Sou o assistente virtual da secretaria. Carregando suas opções...',
            options: [] // Começa vazio e preenche no useEffect
        }
    ]);

    const [loading, setLoading] = useState(false);

    // 1. Atualiza a mensagem de boas-vindas quando o usuário carrega
    useEffect(() => {
        if (user) {
            setMessages(prev => {
                const newMsgs = [...prev];
                // Atualiza a primeira mensagem com o nome e as opções
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

    // 2. Auto-scroll
    useEffect(() => {
        bottomRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages, loading]);

    // --- Lógica do Chat ---
    const handleOptionClick = async (option: Option) => {
        
        // Adiciona mensagem do usuário
        const userMsg: Message = { id: Date.now(), role: 'user', text: option.label.replace(/^[^\w]+/, '') }; 
        setMessages(prev => [...prev, userMsg]);
        setLoading(true);

        // Ação: Navegar
        if (option.action === 'navigate') {
            setTimeout(() => router.push(option.value), 800);
            return;
        }

        // Ação: Buscar Tipos
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
        
        // Ação: Selecionou um Tipo
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
            
            {/* Header */}
            <header className="sticky top-0 z-20 bg-white/90 backdrop-blur-sm border-b border-gray-200 px-6 py-4 shadow-sm">
                <div className="max-w-3xl mx-auto flex items-center gap-3">
                    <div className="relative">
                        <div className="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center text-green-600 font-bold">
                            CR
                        </div>
                        <span className="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white rounded-full"></span>
                    </div>
                    <div>
                        <h1 className="text-lg font-bold text-slate-800 leading-tight">Secretaria Virtual</h1>
                        <p className="text-xs text-green-600 font-medium">Online agora</p>
                    </div>
                </div>
            </header>

            {/* Chat Area */}
            <main className="flex-1 w-full max-w-3xl mx-auto p-4 sm:p-6 pb-24 overflow-y-auto">
                <div className="space-y-6">
                    {messages.map((msg) => (
                        <div key={msg.id} className={`flex w-full ${msg.role === 'user' ? 'justify-end' : 'justify-start'}`}>
                            
                            <div className={`flex max-w-[90%] sm:max-w-[75%] gap-3 ${msg.role === 'user' ? 'flex-row-reverse' : 'flex-row'}`}>
                                
                                {/* Avatar */}
                                <div className={`w-8 h-8 rounded-full flex-shrink-0 flex items-center justify-center text-xs font-bold shadow-sm mt-auto mb-1
                                    ${msg.role === 'user' ? 'bg-indigo-100 text-indigo-600' : 'bg-white text-green-600 border border-gray-200'}`}>
                                    {msg.role === 'user' ? 'EU' : 'CR'}
                                </div>

                                <div className="flex flex-col gap-2">
                                    {/* Balão */}
                                    <div className={`
                                        px-5 py-3.5 text-[15px] leading-relaxed shadow-sm transition-all duration-300
                                        ${msg.role === 'user' 
                                            ? 'bg-[#15803d] text-white rounded-2xl rounded-br-none' 
                                            : 'bg-white border border-gray-100 text-slate-700 rounded-2xl rounded-bl-none'}
                                    `}>
                                        {msg.text}
                                    </div>

                                    {/* Opções (Botões) */}
                                    {msg.role === 'bot' && msg.options && msg.options.length > 0 && (
                                        <div className="flex flex-wrap gap-2 mt-1">
                                            {msg.options.map((opt, idx) => (
                                                <button
                                                    key={idx}
                                                    onClick={() => handleOptionClick(opt)}
                                                    className="
                                                        bg-white border border-green-200 text-[#15803d] 
                                                        px-4 py-2.5 rounded-xl text-sm font-semibold 
                                                        shadow-sm hover:shadow-md hover:bg-green-50 hover:border-green-300 
                                                        active:scale-95 transition-all duration-200
                                                    "
                                                >
                                                    {opt.label}
                                                </button>
                                            ))}
                                        </div>
                                    )}
                                </div>
                            </div>

                        </div>
                    ))}

                    {/* Loading Indicator */}
                    {loading && (
                        <div className="flex w-full justify-start">
                            <div className="flex gap-3 max-w-[85%]">
                                <div className="w-8 h-8 bg-white border border-gray-200 rounded-full flex items-center justify-center text-xs font-bold text-green-600 shadow-sm mt-auto mb-1">
                                    CR
                                </div>
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

            {/* Fake Input Area */}
            <div className="sticky bottom-0 bg-white border-t border-gray-200 p-4">
                <div className="max-w-3xl mx-auto relative opacity-50 cursor-not-allowed">
                    <input 
                        type="text" 
                        placeholder="Selecione uma opção acima..." 
                        disabled
                        className="w-full bg-gray-100 border-0 rounded-full py-3.5 px-5 text-gray-500 focus:ring-0 cursor-not-allowed outline-none"
                    />
                    <div className="absolute right-2 top-1.5 p-2 bg-gray-300 rounded-full text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                    </div>
                </div>
                <p className="text-center text-[10px] text-gray-400 mt-2 uppercase tracking-wider">
                    Ambiente Seguro • ChatRequest v1.0
                </p>
            </div>

        </div>
    );
}