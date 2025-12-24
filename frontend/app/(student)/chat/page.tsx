'use client'

import { useState, useRef, useEffect } from 'react';
import { useAuth } from '@/app/context/AuthContext';
import { useRouter } from 'next/navigation';
import axios from 'axios';

export default function ChatPage() {
    const { user, logout, token } = useAuth();
    const router = useRouter();
    
    // Referência para o fim do chat (auto-scroll)
    const messagesEndRef = useRef<HTMLDivElement>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const [isMenuOpen, setIsMenuOpen] = useState(false);
    const [isUploading, setIsUploading] = useState(false);
    
    // Estado inicial com mensagem de boas-vindas
    const [chatMessages, setChatMessages] = useState<
        { author: 'user' | 'bot', text: string, attachment?: string }[]
    >([
        { author: 'bot', text: 'Olá! Sou a Stella, assistente virtual da secretaria. Como posso ajudar você hoje?' }
    ]);

    const [mensagem, setMensagem] = useState("");

    // Auto-scroll sempre que chegar mensagem nova
    useEffect(() => {
        messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
    }, [chatMessages]);

    const handleConsultar = () => router.push('/dashboard'); // Ajustei para dashboard
    const handleSolicitar = () => router.push('/requests/new');

    const handleMenuClick = (path: string) => {
        setIsMenuOpen(false);
        if (path === 'logout') {
            logout();
            router.push('/login');
        } else router.push(path);
    };

    const handleFileClick = () => fileInputRef.current?.click();

    const handleFileChange = async (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;

        if (file.size > 10 * 1024 * 1024) {
            alert("O arquivo é muito grande (Máx 10MB).");
            return;
        }

        setIsUploading(true);
        const formData = new FormData();
        formData.append('arquivo', file); 

        try {
            const response = await axios.post('http://127.0.0.1:8000/api/documents/upload', formData, {
                headers: {
                    'Authorization': `Bearer ${token}`, 
                    'Content-Type': 'multipart/form-data'
                }
            });

            setChatMessages((prev) => [
                ...prev,
                { 
                    author: 'user', 
                    text: `📎 Arquivo enviado: ${file.name}`,
                    attachment: response.data.url || response.data.public_url 
                }
            ]);

        } catch (error: any) {
            console.error("Erro no upload:", error);
            if (error.response?.status === 422) {
                const dados = error.response.data;
                const listaErros = dados.erros || dados.errors;
                let msgErro = "Erro de validação.";
                
                if (listaErros?.arquivo?.[0]) {
                    msgErro = listaErros.arquivo[0];
                } else if (dados.mensagem) {
                    msgErro = dados.mensagem;
                }
                alert(`❌ Falha no envio: ${msgErro}`);
            } else {
                alert("Erro ao enviar arquivo. Tente novamente.");
            }
        } finally {
            setIsUploading(false);
            if (fileInputRef.current) fileInputRef.current.value = "";
        }
    };

    const handleEnviar = () => {
        if (mensagem.trim().length === 0) return;

        setChatMessages((prev) => [
            ...prev,
            { author: 'user', text: mensagem }
        ]);

        // Simulação de resposta do Bot (Opcional, só para teste visual)
        setTimeout(() => {
             setChatMessages((prev) => [
                ...prev,
                { author: 'bot', text: 'Recebi sua mensagem! Em breve um atendente irá analisar.' }
            ]);
        }, 1000);

        setMensagem("");
    };

    // Enviar com Enter
    const handleKeyDown = (e: React.KeyboardEvent) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            handleEnviar();
        }
    };

    return (
        <div className="min-h-screen bg-gray-50 flex flex-col">
            
            {/* === CABEÇALHO FIXO === */}
            <header className="bg-white shadow-sm sticky top-0 z-20">
                <div className="max-w-3xl mx-auto px-4 py-4 flex justify-between items-center">
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center text-green-700 font-bold text-xl">
                            S
                        </div>
                        <div>
                            <h1 className="text-lg font-bold text-gray-800 leading-tight">Secretaria Virtual</h1>
                            <p className="text-xs text-green-600 font-medium">● Online agora</p>
                        </div>
                    </div>

                    <div className="relative">
                        <button 
                            onClick={() => setIsMenuOpen(!isMenuOpen)}
                            className="p-2 text-gray-500 hover:bg-gray-100 rounded-full transition"
                        >
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path></svg>
                        </button>

                        {isMenuOpen && (
                            <div className="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl border border-gray-100 z-30 overflow-hidden animation-fade-in">
                                <div className="px-4 py-3 bg-gray-50 border-b border-gray-100">
                                    <p className="text-sm font-medium text-gray-900 truncate">{user?.name}</p>
                                    <p className="text-xs text-gray-500 truncate">{user?.email}</p>
                                </div>
                                <button onClick={() => handleMenuClick('/me')} className="w-full px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 text-left flex items-center gap-2">
                                    👤 Meus Dados
                                </button>
                                <button onClick={() => handleMenuClick('logout')} className="w-full px-4 py-3 text-sm text-red-600 hover:bg-red-50 text-left flex items-center gap-2">
                                    🚪 Sair
                                </button>
                            </div>
                        )}
                    </div>
                </div>
            </header>

            {/* === ÁREA PRINCIPAL (COM SCROLL) === */}
            <main className="flex-1 max-w-3xl w-full mx-auto p-4 pb-32">
                
                {/* ATALHOS RÁPIDOS (CARDS) */}
                <div className="grid grid-cols-2 gap-4 mb-8 mt-2">
                    <button 
                        onClick={handleSolicitar}
                        className="group bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md hover:border-green-200 transition-all text-left"
                    >
                        <div className="w-10 h-10 bg-green-50 rounded-lg flex items-center justify-center mb-3 group-hover:bg-green-100 transition-colors">
                            📄
                        </div>
                        <h3 className="font-bold text-gray-800">Novo Pedido</h3>
                        <p className="text-xs text-gray-500 mt-1">Abra um novo requerimento</p>
                    </button>

                    <button 
                        onClick={handleConsultar}
                        className="group bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md hover:border-blue-200 transition-all text-left"
                    >
                        <div className="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center mb-3 group-hover:bg-blue-100 transition-colors">
                            📂
                        </div>
                        <h3 className="font-bold text-gray-800">Meus Pedidos</h3>
                        <p className="text-xs text-gray-500 mt-1">Acompanhe suas solicitações</p>
                    </button>
                </div>

                {/* DIVISOR DE DATA */}
                <div className="flex justify-center mb-6">
                    <span className="bg-gray-200 text-gray-600 text-xs px-3 py-1 rounded-full font-medium">Hoje</span>
                </div>

                {/* MENSAGENS */}
                <div className="space-y-6">
                    {chatMessages.map((msg, index) => (
                        <div key={index} className={`flex ${msg.author === 'user' ? 'justify-end' : 'justify-start'}`}>
                            
                            {msg.author === 'bot' && (
                                <div className="w-8 h-8 bg-green-100 rounded-full flex-shrink-0 flex items-center justify-center mr-2 self-end mb-1 text-xs font-bold text-green-700">
                                    S
                                </div>
                            )}

                            <div className={`
                                max-w-[80%] p-4 rounded-2xl shadow-sm relative text-sm leading-relaxed
                                ${msg.author === 'user' 
                                    ? 'bg-gradient-to-br from-green-600 to-green-500 text-white rounded-br-none shadow-green-100' 
                                    : 'bg-white text-gray-800 border border-gray-100 rounded-bl-none'}
                            `}>
                                {msg.attachment ? (
                                    <a href={msg.attachment} target="_blank" rel="noopener noreferrer" className="flex items-center gap-3 bg-black/10 p-2 rounded-lg hover:bg-black/20 transition">
                                        <div className="bg-white p-2 rounded text-gray-500">
                                            📎
                                        </div>
                                        <div className="flex-1 min-w-0">
                                            <p className="font-medium truncate text-white">{msg.text.replace('📎 Arquivo enviado: ', '')}</p>
                                            <p className="text-xs opacity-80">Clique para visualizar</p>
                                        </div>
                                    </a>
                                ) : (
                                    <p>{msg.text}</p>
                                )}
                                
                                {/* Hora da mensagem (Fictícia por enquanto) */}
                                <span className={`text-[10px] absolute bottom-1 right-3 ${msg.author === 'user' ? 'text-green-100' : 'text-gray-400'}`}>
                                    {new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                                </span>
                            </div>
                        </div>
                    ))}

                    {isUploading && (
                        <div className="flex justify-end">
                            <div className="bg-gray-100 text-gray-500 text-xs py-2 px-4 rounded-full animate-pulse">
                                Enviando arquivo... 📤
                            </div>
                        </div>
                    )}
                    
                    {/* Elemento invisível para scroll automático */}
                    <div ref={messagesEndRef} />
                </div>
            </main>

            {/* === BARRA DE INPUT FLUTUANTE === */}
            <div className="fixed bottom-0 left-0 w-full p-4 bg-gradient-to-t from-gray-50 via-gray-50 to-transparent pt-10">
                <div className="max-w-3xl mx-auto">
                    <div className="bg-white p-2 rounded-2xl shadow-xl border border-gray-200 flex items-center gap-2">
                        
                        {/* Input de Arquivo Escondido */}
                        <input 
                            type="file" 
                            ref={fileInputRef} 
                            onChange={handleFileChange} 
                            className="hidden" 
                            accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                        />

                        <button 
                            onClick={handleFileClick}
                            className="p-3 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded-xl transition-all disabled:opacity-50"
                            disabled={isUploading}
                            title="Anexar arquivo"
                        >
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                        </button>

                        <input
                            value={mensagem}
                            onChange={(e) => setMensagem(e.target.value)}
                            onKeyDown={handleKeyDown}
                            placeholder="Digite sua mensagem..."
                            className="flex-1 bg-transparent outline-none text-gray-700 placeholder-gray-400 px-2"
                        />

                        <button
                            onClick={handleEnviar}
                            disabled={mensagem.trim().length === 0}
                            className={`p-3 rounded-xl transition-all transform duration-200 ${
                                mensagem.trim().length === 0 
                                ? "bg-gray-100 text-gray-400 cursor-not-allowed" 
                                : "bg-green-600 text-white hover:bg-green-700 hover:scale-105 shadow-md"
                            }`}
                        >
                            <svg className="w-5 h-5 translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                        </button>
                    </div>
                    <p className="text-center text-[10px] text-gray-400 mt-2">
                        Pressione Enter para enviar
                    </p>
                </div>
            </div>

        </div>
    );
}