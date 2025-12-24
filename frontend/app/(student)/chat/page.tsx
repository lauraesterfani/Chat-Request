'use client'

import { useState, useRef } from 'react';
import { useAuth } from '@/app/context/AuthContext';
import { useRouter } from 'next/navigation';
import axios from 'axios';

export default function ChatPage() {
    const { user, logout, token } = useAuth(); // Precisamos do token para o upload
    const router = useRouter();

    // Referência para o input de arquivo (invisível)
    const fileInputRef = useRef<HTMLInputElement>(null);

    const [isMenuOpen, setIsMenuOpen] = useState(false);
    const [isUploading, setIsUploading] = useState(false);
    
    // Mensagens do chat
    const [chatMessages, setChatMessages] = useState<
        { author: 'user' | 'bot', text: string, attachment?: string }[]
    >([]);

    const [mensagem, setMensagem] = useState("");

    // Navegação
    const handleConsultar = () => router.push('/requests');
    const handleSolicitar = () => router.push('/requests/new');

    const handleMenuClick = (path: string) => {
        setIsMenuOpen(false);
        if (path === 'logout') {
            logout();
            router.push('/login');
        } else router.push(path);
    };

    // --- FUNÇÃO DE UPLOAD CORRIGIDA ---
    const handleFileClick = () => {
        // Abre a janela de seleção de arquivo
        fileInputRef.current?.click();
    };

    const handleFileChange = async (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;

        // 1. Validação Visual (Frontend)
        // Limite de 10MB para avisar o usuário antes de enviar
        if (file.size > 10 * 1024 * 1024) {
            alert("O arquivo é muito grande (Máx 10MB).");
            return;
        }

        setIsUploading(true);

        const formData = new FormData();
        // IMPORTANTE: O nome 'arquivo' deve ser igual ao validado no DocumentController
        formData.append('arquivo', file); 

        try {
            console.log("Enviando arquivo...");

            const response = await axios.post('http://127.0.0.1:8000/api/documents/upload', formData, {
                headers: {
                    'Authorization': `Bearer ${token}`, 
                    'Content-Type': 'multipart/form-data'
                }
            });

            // Se der certo, mostra no chat
            console.log("Upload sucesso:", response.data);
            
            setChatMessages((prev) => [
                ...prev,
                { 
                    author: 'user', 
                    text: `📎 Arquivo enviado: ${file.name}`,
                    attachment: response.data.public_url 
                }
            ]);

        } catch (error: any) {
            console.error("Erro no upload:", error);
            
            // TRATAMENTO DO ERRO 422 (Validação do Laravel)
            if (error.response?.status === 422) {
                const dados = error.response.data;
                // Seu controller retorna 'erros' (em português), mas o Laravel padrão usa 'errors'
                const listaErros = dados.erros || dados.errors;
                
                let msgErro = "Erro de validação.";
                
                // Tenta pegar a mensagem específica do campo 'arquivo'
                if (listaErros?.arquivo?.[0]) {
                    msgErro = listaErros.arquivo[0];
                } else if (dados.mensagem) {
                    msgErro = dados.mensagem;
                }

                alert(`❌ Falha no envio: ${msgErro}\n\nDICA: Se o erro for "O campo arquivo é obrigatório", aumente o 'upload_max_filesize' no seu php.ini.`);
            } else {
                alert("Erro ao enviar arquivo. Verifique se o backend está rodando.");
            }
        } finally {
            setIsUploading(false);
            if (fileInputRef.current) fileInputRef.current.value = ""; // Limpa o input
        }
    };

    // Enviar mensagem de texto
    const handleEnviar = () => {
        if (mensagem.trim().length === 0) return;

        setChatMessages((prev) => [
            ...prev,
            { author: 'user', text: mensagem }
        ]);

        setMensagem("");
    };

    return (
        <div className="max-w-3xl mx-auto py-10 relative pb-28">

            {/* CABEÇALHO */}
            <div className="flex justify-between items-center mb-8 px-4 sm:px-0">
                <h1 className="text-3xl font-light text-gray-700">
                    Olá, {user?.name.split(' ')[0]}!
                </h1>

                <div className="relative">
                    <button
                        onClick={() => setIsMenuOpen(!isMenuOpen)}
                        className="p-2 text-gray-600 hover:text-gray-900 focus:ring-2 focus:ring-green-500 rounded-full"
                    >
                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>

                    {isMenuOpen && (
                        <div className="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-100 z-10">
                            <button onClick={() => handleMenuClick('/me')} className="w-full px-4 py-2 text-sm text-gray-700 hover:bg-green-50 text-left">Dados da Conta</button>
                            <button onClick={() => handleMenuClick('logout')} className="w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50 border-t border-gray-100 text-left">Sair</button>
                        </div>
                    )}
                </div>
            </div>

            {/* AÇÕES */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mt-10">
                <button onClick={handleSolicitar} className="p-8 bg-white rounded-lg shadow-md hover:shadow-lg text-xl text-gray-700">Solicitar Requerimento</button>
                <button onClick={handleConsultar} className="p-8 bg-white rounded-lg shadow-md hover:shadow-lg text-xl text-gray-700">Consultar Requerimento</button>
            </div>

            {/* CHAT */}
            <div className="mt-12 space-y-4 px-4 sm:px-0">
                <div className="flex justify-start">
                    <div className="bg-white text-gray-800 p-4 rounded-lg rounded-bl-none shadow-md">
                        Olá! Bem-vindo. Selecione uma opção acima ou envie um anexo.
                    </div>
                </div>

                {chatMessages.map((msg, index) => (
                    <div key={index} className={`flex ${msg.author === 'user' ? 'justify-end' : 'justify-start'}`}>
                        <div className={`p-4 rounded-lg shadow-md max-w-xs ${msg.author === 'user' ? 'bg-green-600 text-white rounded-br-none' : 'bg-white text-gray-800 rounded-bl-none'}`}>
                            {msg.attachment ? (
                                <a href={msg.attachment} target="_blank" rel="noopener noreferrer" className="flex items-center gap-2 underline text-white break-all">
                                    <svg className="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                                    {msg.text}
                                </a>
                            ) : msg.text}
                        </div>
                    </div>
                ))}
                
                {isUploading && (
                    <div className="text-right text-sm text-gray-500 animate-pulse">Enviando arquivo...</div>
                )}
            </div>

            {/* CAIXA DE TEXTO */}
            <div className="fixed bottom-0 left-0 w-full bg-white border-t border-gray-200 py-3 px-4">
                <div className="max-w-3xl mx-auto flex gap-3 items-center">
                    
                    {/* INPUT DE ARQUIVO (Escondido) */}
                    <input 
                        type="file" 
                        ref={fileInputRef} 
                        onChange={handleFileChange} 
                        className="hidden" 
                        accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                    />

                    {/* BOTÃO CLIPS */}
                    <button 
                        onClick={handleFileClick}
                        className="p-2 text-gray-400 hover:text-gray-600 transition hover:bg-gray-100 rounded-full"
                        title="Anexar arquivo"
                        disabled={isUploading}
                    >
                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                        </svg>
                    </button>

                    <input
                        value={mensagem}
                        onChange={(e) => setMensagem(e.target.value)}
                        placeholder="Digite uma mensagem..."
                        className="flex-1 p-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 outline-none"
                    />

                    <button
                        onClick={handleEnviar}
                        disabled={mensagem.trim().length === 0}
                        className={`p-3 rounded-full transition ${mensagem.trim().length === 0 ? "text-gray-400 cursor-not-allowed" : "text-green-600 hover:text-green-700"}`}
                    >
                        <svg className="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3.4 20.6 21 12 3.4 3.4 3 10l11 2-11 2.1z" /></svg>
                    </button>
                </div>
            </div>
        </div>
    );
}