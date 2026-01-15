'use client';

import { useState } from 'react';
import { useAuth } from '../context/AuthContext';
import { useRouter } from 'next/navigation'; // Import para redirecionamento
import Link from 'next/link';
import Image from 'next/image';

// Ícone de Carregamento
const LoaderCircle = (props: React.SVGProps<SVGSVGElement>) => (
    <svg {...props} xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="animate-spin">
        <path d="M21 12a9 9 0 1 1-6.219-8.56" />
    </svg>
);

export default function CradtLoginPage() {
  // ALTERADO: Usamos setToken em vez de login
  const { setToken } = useAuth();
  const router = useRouter();
  
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      // 1. Faz a requisição de Login manualmente
      const response = await fetch('http://127.0.0.1:8000/api/login/staff', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({ email, password }),
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || 'Credenciais inválidas.');
      }

      // 2. Verificação de Segurança: Bloqueia alunos nesta tela
      if (data.user.role === 'student') {
          setError('Acesso negado. Alunos devem utilizar o Portal do Estudante.');
          setLoading(false);
          return;
      }

      // 3. SUCESSO: Salva o token e redireciona
      // O AuthContext vai detetar o token e carregar o utilizador automaticamente
      setToken(data.token);
      
      // Redireciona para o dashboard administrativo
      router.push('/dashboard'); 

    } catch (err: any) {
      setError(err.message || 'Ocorreu um erro ao tentar entrar.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen w-full flex bg-[#0f172a] font-sans overflow-hidden">
      
      {/* LADO ESQUERDO: DESTAQUE INSTITUCIONAL */}
      <div className="hidden lg:flex lg:w-[60%] relative flex-col justify-end p-16 overflow-hidden">
         <div className="absolute inset-0 bg-[#020617]">
            <div className="absolute top-[-20%] right-[-10%] w-[800px] h-[800px] bg-blue-600/20 rounded-full blur-[120px]"></div>
            <div className="absolute bottom-[-20%] left-[-10%] w-[600px] h-[600px] bg-indigo-600/10 rounded-full blur-[100px]"></div>
            <div className="absolute inset-0 bg-[url('/grid-pattern.svg')] opacity-5"></div>
         </div>

         <div className="relative z-10 max-w-2xl">
             <div className="mb-6 inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-400 text-xs font-medium uppercase tracking-wider">
                Acesso Restrito
             </div>
             <h1 className="text-5xl font-bold text-white mb-6 leading-tight">
                Gestão Acadêmica <br/>
                <span className="text-blue-500">Eficiente e Segura.</span>
             </h1>
             <p className="text-slate-400 text-lg leading-relaxed">
                Bem-vindo ao Sistema CRADT. Utilize suas credenciais institucionais para gerenciar requerimentos, analisar processos e comunicar-se com os discentes.
             </p>
         </div>
      </div>


      {/* LADO DIREITO: FORMULÁRIO DE LOGIN */}
      <div className="w-full lg:w-[40%] bg-white flex flex-col justify-center items-center px-8 md:px-16 lg:px-24 relative">
        
        <Link href="/" className="absolute top-8 right-8 text-slate-400 hover:text-slate-600 transition-colors lg:hidden">
            Voltar
        </Link>

        <div className="w-full max-w-sm">
            
            <div className="mb-10">
                <div className="flex items-center gap-3 mb-6">
                   <div className="w-10 h-10 bg-[#0f172a] rounded-lg flex items-center justify-center text-white font-bold shadow-lg shadow-slate-900/20">
                     CR
                   </div>
                   <span className="text-xl font-bold text-[#0f172a] tracking-tight">Chat Request <span className="font-normal text-slate-500">| Staff</span></span>
                </div>
                
                <h2 className="text-2xl font-bold text-[#0f172a]">Acesso Administrativo</h2>
                <p className="text-slate-500 mt-2">Insira suas credenciais para continuar.</p>
            </div>

            {error && (
               <div className="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm rounded-r shadow-sm flex items-start gap-3">
                 <span>{error}</span>
               </div>
            )}

            <form onSubmit={handleLogin} className="space-y-5">
                
                <div>
                    <label className="block text-sm font-semibold text-slate-700 mb-1.5">E-mail Institucional</label>
                    <input
                        type="email"
                        value={email}
                        onChange={(e) => setEmail(e.target.value)}
                        className="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-100 outline-none transition-all text-slate-800 placeholder:text-slate-400"
                        placeholder="nome@cradt.edu.br"
                        required
                    />
                </div>

                <div>
                    <div className="flex justify-between items-center mb-1.5">
                        <label className="block text-sm font-semibold text-slate-700">Senha</label>
                    </div>
                    <input
                        type="password"
                        value={password}
                        onChange={(e) => setPassword(e.target.value)}
                        className="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-blue-600 focus:ring-2 focus:ring-blue-100 outline-none transition-all text-slate-800 placeholder:text-slate-400"
                        placeholder="••••••••"
                        required
                    />
                </div>

                <button
                    type="submit"
                    disabled={loading}
                    className="w-full bg-[#0f172a] text-white font-bold py-3.5 rounded-xl hover:bg-slate-800 transition-all shadow-lg shadow-slate-900/20 active:scale-[0.98] disabled:opacity-70 disabled:cursor-not-allowed mt-2 flex items-center justify-center gap-2"
                >
                    {loading ? (
                         <>
                           <LoaderCircle className="w-5 h-5" />
                           Autenticando...
                         </>
                    ) : (
                        "Acessar Painel"
                    )}
                </button>

            </form>

            <div className="mt-8 pt-8 border-t border-slate-100 text-center">
                <p className="text-xs text-slate-400">
                    Problemas com acesso? Contate o suporte técnico.
                </p>
            </div>
            
        </div>
      </div>
    </div>
    
  );
}