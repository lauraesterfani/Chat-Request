'use client';

import { useState } from 'react';
import { useAuth } from '../../context/AuthContext';
import Link from 'next/link';
import Image from 'next/image';

// Ícone de Carregamento
const LoaderCircle = (props: React.SVGProps<SVGSVGElement>) => (
    <svg {...props} xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="animate-spin">
        <path d="M21 12a9 9 0 1 1-6.219-8.56" />
    </svg>
);

export default function StudentLoginPage() {
  const { login } = useAuth();
  
  // Estados
  const [cpf, setCpf] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      const result = await login({ cpf, password });
      
      if (!result.success) {
          setError(result.message || 'Credenciais inválidas.');
      }
      // Se sucesso, o AuthContext redireciona automaticamente para /me

    } catch (err: any) {
      setError('Ocorreu um erro inesperado. Tente novamente.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen w-full flex bg-white font-sans overflow-hidden">
      
      {/* =====================================================
          LADO ESQUERDO: BRANDING ALUNO (Verde com Gradiente)
      ===================================================== */}
      <div className="hidden lg:flex lg:w-[55%] relative flex-col justify-center items-center p-12 overflow-hidden">
         
         {/* Fundo com Gradiente e Elementos */}
         {/* MUDANÇA PRINCIPAL: Adicionei o gradiente 'bg-gradient-to-br from-green-900 to-[#15803d]' */}
         <div className="absolute inset-0 bg-gradient-to-br from-green-900 to-[#15803d]">
            {/* Efeitos de luz de fundo (ajustados para verde) */}
            <div className="absolute top-[-20%] left-[-10%] w-[700px] h-[700px] bg-green-500/20 rounded-full blur-[120px]"></div>
            <div className="absolute bottom-[-20%] right-[-10%] w-[600px] h-[600px] bg-emerald-400/20 rounded-full blur-[100px]"></div>
            
            {/* Padrão de Grid sutil */}
            <div className="absolute inset-0 bg-[url('/grid-pattern.svg')] opacity-10 mix-blend-overlay"></div>
         </div>

         <div className="relative z-10 flex flex-col items-center text-center max-w-lg animate-fade-in-up">
             {/* Mascote com destaque */}
             <div className="w-80 h-80 relative mb-8 transform hover:scale-105 transition-transform duration-500">
                <Image 
                    src="/mascote.png" 
                    alt="Mascote Estudante" 
                    fill
                    className="object-contain drop-shadow-2xl"
                    priority
                />
             </div>
             
             <h1 className="text-4xl font-bold text-white mb-4 drop-shadow-sm">
                Bem-vindo de volta!
             </h1>
             <p className="text-green-50 text-lg leading-relaxed font-medium drop-shadow-sm">
                Acompanhe seus requerimentos em tempo real e resolva suas pendências acadêmicas sem sair de casa.
             </p>
         </div>

         <div className="absolute bottom-8 text-green-200/80 text-sm font-medium">
            ChatRequest © 2025
         </div>
      </div>


      {/* =====================================================
          LADO DIREITO: FORMULÁRIO (Branco)
          (Esta parte permanece igual)
      ===================================================== */}
      <div className="w-full lg:w-[45%] flex flex-col justify-center items-center px-8 md:px-16 relative bg-white">
        
        {/* Link para voltar (Mobile apenas) */}
        <Link href="/" className="absolute top-8 left-8 lg:hidden text-slate-400 hover:text-[#15803d] transition-colors flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Voltar
        </Link>

        {/* Botão de ajuda/CRADT (Topo direita) */}
        <div className="absolute top-8 right-8">
            <Link href="/cradt-login" className="text-sm font-medium text-slate-400 hover:text-[#15803d] transition-colors">
                Sou Servidor
            </Link>
        </div>

        <div className="w-full max-w-sm">
            
            {/* Cabeçalho Mobile (Logo) */}
            <div className="flex flex-col items-center mb-10">
                <Link href="/" className="inline-flex items-center gap-2 mb-8 lg:hidden">
                    <div className="w-10 h-10 bg-[#15803d] rounded-lg flex items-center justify-center">
                        <span className="text-white font-bold">CR</span>
                    </div>
                    <span className="text-2xl font-bold text-[#0f172a]">ChatRequest</span>
                </Link>
                
                <h2 className="text-3xl font-bold text-[#0f172a] self-start">Login do Aluno</h2>
                <p className="text-slate-500 mt-2 self-start">Entre com seu CPF e senha.</p>
            </div>

            {/* Mensagem de Erro */}
            {error && (
               <div className="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm rounded-r shadow-sm flex items-start gap-3 animate-shake">
                 <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                   <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                 </svg>
                 <span>{error}</span>
               </div>
            )}

            <form onSubmit={handleLogin} className="space-y-6">
                
                {/* Campo CPF */}
                <div>
                    <label className="block text-sm font-semibold text-slate-700 mb-2">CPF</label>
                    <div className="relative">
                        <span className="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        </span>
                        <input
                            type="text"
                            value={cpf}
                            onChange={(e) => setCpf(e.target.value)}
                            className="w-full pl-11 pr-4 py-4 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-[#15803d] focus:ring-4 focus:ring-green-100 outline-none transition-all text-slate-800 font-medium placeholder:text-slate-400"
                            placeholder="000.000.000-00"
                            required
                        />
                    </div>
                </div>

                {/* Campo Senha */}
                <div>
                    <div className="flex justify-between items-center mb-2">
                        <label className="block text-sm font-semibold text-slate-700">Senha</label>
                        <Link href="#" className="text-sm font-medium text-[#15803d] hover:underline">
                            Esqueceu?
                        </Link>
                    </div>
                    <div className="relative">
                        <span className="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        </span>
                        <input
                            type="password"
                            value={password}
                            onChange={(e) => setPassword(e.target.value)}
                            className="w-full pl-11 pr-4 py-4 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-[#15803d] focus:ring-4 focus:ring-green-100 outline-none transition-all text-slate-800 font-medium placeholder:text-slate-400"
                            placeholder="••••••••"
                            required
                        />
                    </div>
                </div>

                {/* Botão de Login */}
                <button
                    type="submit"
                    disabled={loading}
                    className="w-full bg-[#15803d] text-white font-bold text-lg py-4 rounded-xl hover:bg-[#166534] transition-all shadow-lg shadow-green-700/20 hover:shadow-green-700/40 active:scale-[0.98] disabled:opacity-70 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                >
                    {loading ? (
                         <>
                           <LoaderCircle className="w-5 h-5 text-white" />
                           Entrando...
                         </>
                    ) : (
                        "Entrar na Plataforma"
                    )}
                </button>

            </form>

            {/* Rodapé (Criar Conta) */}
            <p className="mt-8 text-center text-slate-600">
                Ainda não tem acesso?{" "}
                <Link href="/signup" className="text-[#15803d] font-bold hover:underline">
                    Criar conta agora
                </Link>
            </p>
            
        </div>
      </div>
    </div>
  );
}