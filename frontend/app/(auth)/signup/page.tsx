'use client'; // Mantido, embora não seja estritamente necessário neste ambiente

import React, { useState, useEffect, useCallback } from 'react';

// --------------------------------------------------------------------
// 🎯 INTERFACES (Para resolver erros de tipo 'never' e 'SetStateAction<null>')
// --------------------------------------------------------------------
interface Course {
    id: string;
    name: string;
}
// --------------------------------------------------------------------

// ====================================================================
// SIMULAÇÃO DE DEPENDÊNCIAS EXTERNAS PARA AMBIENTE DE VISUALIZAÇÃO
// ====================================================================

// Simulação de useRouter para ambiente de visualização
// Adicionada anotação de tipo para 'path'
const useRouter = () => ({
    push: (path: string) => console.log(`[SIMULAÇÃO] Redirecionamento para: ${path}`),
    // Em um um app real do Next.js, esta função faria o redirecionamento.
});

// Simulação de AuthContext
const useAuth = () => {
    // Adicionada anotação de tipo para 'userData'
    const register = useCallback(async (userData: any) => {
        console.log("[SIMULAÇÃO] Tentativa de Registo com:", userData);
        
        // Simulação de atraso de API
        await new Promise(resolve => setTimeout(resolve, 1500)); 

        // Simulação de sucesso para fins de demonstração
        if (userData.email.includes("fail")) {
            return { success: false, message: "Este e-mail já está em uso (simulação)." };
        }
        return { success: true, message: "Registo bem-sucedido." };
    }, []);

    // Estado de autenticação simulado
    const isAuthenticated = false; 
    const isLoading = false; // Define como falso para exibir o formulário

    return { register, isAuthenticated, isLoading };
};

// Simulação de Link e Image (usando tags HTML padrão e um placeholder)
// Adicionadas anotações de tipo para props
const Link = ({ href, children, className }: { href: string, children: React.ReactNode, className: string }) => (
    <a href="#" onClick={() => console.log(`[SIMULAÇÃO] Link clicado para ${href}`)} className={className}>
        {children}
    </a>
);

// Adicionadas anotações de tipo para props
const Image = ({ src, alt, fill, className, priority }: { src: string, alt: string, fill?: boolean, className: string, priority?: boolean }) => (
    <img 
        src="https://placehold.co/256x256/15803d/ffffff?text=Mascote" 
        alt={alt} 
        className={className + " w-full h-full object-cover"} // Simula fill/object-contain
        style={{ width: '100%', height: '100%' }}
    />
);
// ====================================================================
// FIM DA SIMULAÇÃO
// ====================================================================


// O código é convertido para JSX (React simples) para ser compilado
// Adicionada anotação de tipo para props
const LoaderCircle = (props: React.SVGProps<SVGSVGElement>) => (
    <svg {...props} xmlns="http://www.w3.org/2000/svg" width="24" height="24" 
        viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" 
        strokeLinecap="round" strokeLinejoin="round" className="animate-spin">
        <path d="M21 12a9 9 o 1 1-6.219-8.56" />
    </svg>
);

// 💡 COMPONENTE: Ícone de Olho para alternar a visibilidade da senha
const EyeIcon = (props: React.SVGProps<SVGSVGElement> & { isVisible: boolean, onClick: () => void }) => (
    <button 
        type="button" 
        onClick={props.onClick} 
        className="p-2 text-gray-400 hover:text-gray-600 transition duration-150 absolute right-2 top-1/2 -translate-y-1/2 focus:outline-none focus:ring-2 focus:ring-[#15803d] rounded-full"
        aria-label={props.isVisible ? "Ocultar senha" : "Visualizar senha"}
    >
        {props.isVisible ? (
            // Eye-off (Ocultar)
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24" />
                <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68" />
                <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.48-1.6" />
                <line x1="2" x2="22" y1="2" y2="22" />
            </svg>
        ) : (
            // Eye (Mostrar)
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z" />
                <circle cx="12" cy="12" r="3" />
            </svg>
        )}
    </button>
);


export default function SignupPage() {

    const router = useRouter();
    const { register, isAuthenticated, isLoading } = useAuth();
    
    // Campos de cadastro existentes
    const [name, setName] = useState('');
    const [cpf, setCpf] = useState('');
    const [email, setEmail] = useState('');
    const [phone, setPhone] = useState('');
    const [birthday, setBirthday] = useState('');
    const [password, setPassword] = useState('');
    const [passwordConfirmation, setPasswordConfirmation] = useState('');

    // --- CAMPOS ESPECÍFICOS ---
    const [matricula, setMatricula] = useState('');
    const [courseId, setCourseId] = useState(''); 
    const [courses, setCourses] = useState<Course[]>([]); 
    
    // 💡 ESTADOS PARA VISUALIZAÇÃO DE SENHA
    const [showPassword, setShowPassword] = useState(false);
    const [showConfirmationPassword, setShowConfirmationPassword] = useState(false);
    
    // 🟢 ESTADO PARA CONTROLE DE SUCESSO
    const [isSuccess, setIsSuccess] = useState(false); 
    
    // 🟠 CORREÇÃO: Inicializa com null para não mostrar a mensagem no início.
    const [error, setError] = useState<string | null>(null); 
    const [isProcessing, setIsProcessing] = useState(false);
    const [isCoursesLoading, setIsCoursesLoading] = useState(true);

    // 1. 🔥 Redirecionamento após autenticação (Simulado)
    useEffect(() => {
        if (!isLoading && isAuthenticated) {
            router.push('/me');
        }
    }, [isAuthenticated, isLoading]); // router foi removido do array de dependências para a simulação

    // 2. 📚 Carregar Cursos do Backend (Simulado)
    useEffect(() => {
        const fetchCourses = async () => {
            // MOCK DE DADOS PARA TESTE:
            const mockCourses: Course[] = [
                { id: 'logistica-id', name: 'Logística' },
                { id: 'ipi-id', name: 'Informática para Internet (IPI)' },
                { id: 'administracao-id', name: 'Administração' },
                { id: 'gestao-qualidade-id', name: 'Gestão da Qualidade' },
                { id: 'tsi-id', name: 'Tecnologia em Sistemas para Internet (TSI)' },
            ];
            
            setCourses(mockCourses);
            setIsCoursesLoading(false);
        };
        fetchCourses();
    }, []);

    // 3. 🟢 Redirecionamento após cadastro bem-sucedido
    useEffect(() => {
        if (isSuccess) {
            // Repurpondo o estado 'error' para mostrar a mensagem de sucesso com a cor verde
            setError("Conta criada com sucesso! Redirecionando para a página de Login..."); 

            const timer = setTimeout(() => {
                router.push('/login'); // Redireciona para o login
            }, 3000); // Redireciona após 3 segundos
            
            return () => clearTimeout(timer); // Limpa o timer
        }
    }, [isSuccess]);

    // Adicionada anotação de tipo para 'e'
    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setError(null);
        setIsSuccess(false); // Reseta o estado de sucesso em um novo envio

        if (password !== passwordConfirmation) {
            setError("As senhas não correspondem.");
            return;
        }
        
        if (!name || !cpf || !email || !password || !phone || !birthday || !matricula || !courseId) {
            // 🟠 MENSAGEM DE ERRO AO SUBMETER:
            setError("Por favor, preencha todos os campos obrigatórios e selecione um curso.");
            return;
        }

        setIsProcessing(true);

        const userData = { 
            name, 
            cpf, 
            email, 
            phone,
            birthday,
            matricula,      
            course_id: courseId, 
            password,
            password_confirmation: passwordConfirmation 
        };

        try {
            const result = await register(userData);

            if (result.success) {
                // 🟢 Seta o sucesso. O redirecionamento será tratado pelo useEffect.
                setIsSuccess(true); 
            } else {
                setError(result.message || "Erro ao realizar cadastro.");
            }

        } catch (err) {
            setError("Ocorreu um erro inesperado na comunicação.");
        }

        setIsProcessing(false);
    };

    if (isLoading || isAuthenticated) return null;

    return (
        <div className="min-h-screen w-full flex flex-col lg:flex-row bg-white font-sans">
            
            {/* LADO ESQUERDO: BRANDING (Apenas Desktop) */}
            <div className="hidden lg:flex lg:w-[45%] xl:w-[40%] bg-[#15803d] relative flex-col justify-between p-12 overflow-hidden h-screen sticky top-0">
                <div className="relative z-10 flex items-center gap-3">
                   <div className="w-10 h-10 bg-white/20 backdrop-blur-sm rounded-lg flex items-center justify-center text-white font-bold">CR</div>
                   <span className="text-white font-bold text-xl tracking-tight">Chat Request</span>
                </div>

                <div className="relative z-10 flex flex-col items-start">
                   <div className="w-64 h-64 relative mb-8 self-center">
                      <Image 
                          src="https://placehold.co/256x256/15803d/ffffff?text=Mascote" 
                          alt="Mascote" 
                          className="object-contain drop-shadow-2xl"
                          priority
                      />
                   </div>
                   <h2 className="text-4xl font-bold text-white mb-4 leading-tight">
                      Sua jornada acadêmica,<br/>simplificada.
                   </h2>
                   <p className="text-green-100 text-lg leading-relaxed max-w-md">
                      Crie sua conta em segundos e tenha acesso direto à secretaria sem filas e sem burocracia.
                   </p>
                </div>

                <div className="relative z-10 text-sm text-green-200/80">
                    © 2025 Chat Request Inc.
                </div>

                {/* Elementos Decorativos de Fundo */}
                <div className="absolute top-0 left-0 w-full h-full opacity-10 bg-[url('/grid-pattern.svg')]"></div>
                <div className="absolute bottom-0 right-0 w-[500px] h-[500px] bg-green-400 rounded-full blur-[120px] opacity-20 translate-x-1/3 translate-y-1/3"></div>
            </div>

            {/* LADO DIREITO: FORMULÁRIO */}
            <div className="flex-1 flex flex-col justify-center items-center px-6 py-12 md:px-12 lg:px-16 xl:px-24 overflow-y-auto">
                <div className="w-full max-w-xl">
                    
                    <div className="lg:hidden mb-10 text-center">
                       <Link href="/" className="inline-flex items-center gap-2 justify-center mb-4">
                            <div className="w-10 h-10 bg-[#15803d] rounded-xl flex items-center justify-center">
                              <span className="text-white font-bold">CR</span>
                            </div>
                            <span className="text-2xl font-bold text-[#0f172a]">Chat Request</span>
                       </Link>
                       <h1 className="text-3xl font-bold text-[#0f172a] mt-2">Criar conta</h1>
                    </div>

                    <div className="hidden lg:block mb-10">
                      <h1 className="text-3xl font-bold text-[#0f172a]">Criar conta</h1>
                      <p className="text-gray-500 mt-2 text-lg">Preencha seus dados para começar.</p>
                    </div>

                    {/* 🟢 MENSAGEM DE SUCESSO/ERRO: Agora só exibe se 'error' não for null */}
                    {error && (
                      <div className={`mb-8 p-4 border rounded-xl flex items-start gap-3 ${
                          isSuccess 
                          ? 'bg-green-50 border-green-100 text-green-700' 
                          : 'bg-red-50 border-red-100 text-red-700' 
                      }`}>
                        <span>{error}</span>
                      </div>
                    )}
                    
                    <form onSubmit={handleSubmit} className="space-y-6">
                        
                        {/* 1. Nome Completo */}
                        <div>
                            <label className="block text-sm font-semibold text-gray-700 mb-2 ml-1">Nome Completo</label>
                            <input
                                type="text" 
                                placeholder="Ex: Maria Oliveira"
                                value={name}
                                onChange={(e) => setName(e.target.value)}
                                required
                                className="w-full px-5 py-4 bg-gray-50 border border-gray-200 rounded-2xl focus:bg-white focus:border-[#15803d] focus:ring-4 focus:ring-green-100 outline-none transition-all text-gray-800 font-medium"
                            />
                        </div>

                        {/* 2. Grid Matrícula + Curso (MOVIDO PARA CIMA) */}
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                              <label className="block text-sm font-semibold text-gray-700 mb-2 ml-1">Matrícula</label>
                              <input
                                type="text"
                                value={matricula}
                                onChange={(e) => setMatricula(e.target.value)}
                                className="w-full px-5 py-4 bg-gray-50 border border-gray-200 rounded-2xl focus:bg-white focus:border-[#15803d] focus:ring-4 focus:ring-green-100 outline-none transition-all text-gray-800 font-medium"
                                placeholder="2024tsiig0000"
                                required
                              />
                            </div>
                            <div>
                              <label className="block text-sm font-semibold text-gray-700 mb-2 ml-1">Curso</label>
                              {isCoursesLoading ? (
                                <div className="w-full px-5 py-4 bg-gray-50 border border-gray-200 rounded-2xl text-gray-500 flex items-center">
                                    <LoaderCircle className="w-4 h-4 mr-2" /> Carregando cursos...
                                </div>
                              ) : (
                                <select
                                  value={courseId}
                                  onChange={(e) => setCourseId(e.target.value)}
                                  className={`w-full px-5 py-4 bg-gray-50 border border-gray-200 rounded-2xl focus:bg-white focus:border-[#15803d] focus:ring-4 focus:ring-green-100 outline-none transition-all font-medium ${courseId === '' ? 'text-gray-500' : 'text-gray-800'}`}
                                  required
                                >
                                  <option value="" disabled hidden>
                                    Escolher curso
                                  </option>
                                  {courses.map((course) => (
                                    <option key={course.id} value={course.id} className="text-gray-800">
                                      {course.name}
                                    </option>
                                  ))}
                                </select>
                              )}
                            </div>
                        </div>

                        {/* 3. Grid CPF + Data (MOVIDO PARA BAIXO) */}
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                              <label className="block text-sm font-semibold text-gray-700 mb-2 ml-1">CPF</label>
                              <input
                                type="text"
                                value={cpf}
                                onChange={(e) => setCpf(e.target.value)}
                                className="w-full px-5 py-4 bg-gray-50 border border-gray-200 rounded-2xl focus:bg-white focus:border-[#15803d] focus:ring-4 focus:ring-green-100 outline-none transition-all text-gray-800 font-medium"
                                placeholder="000.000.000-00"
                                required
                              />
                            </div>
                            <div>
                              <label className="block text-sm font-semibold text-gray-700 mb-2 ml-1">Data de Nascimento</label>
                              <input
                                type="date"
                                value={birthday}
                                onChange={(e) => setBirthday(e.target.value)}
                                className="w-full px-5 py-4 bg-gray-50 border border-gray-200 rounded-2xl focus:bg-white focus:border-[#15803d] focus:ring-4 focus:ring-green-100 outline-none transition-all text-gray-600 font-medium"
                                required
                              />
                            </div>
                        </div>


                        {/* 4. Grid Email + Tel */}
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                             <div>
                                 <label className="block text-sm font-semibold text-gray-700 mb-2 ml-1">E-mail</label>
                                 <input
                                     type="email"
                                     value={email}
                                     onChange={(e) => setEmail(e.target.value)}
                                     className="w-full px-5 py-4 bg-gray-50 border border-gray-200 rounded-2xl focus:bg-white focus:border-[#15803d] focus:ring-4 focus:ring-green-100 outline-none transition-all text-gray-800 font-medium"
                                     placeholder="email@exemplo.com"
                                     required
                                 />
                             </div>
                             <div>
                                 <label className="block text-sm font-semibold text-gray-700 mb-2 ml-1">Telefone</label>
                                 <input
                                     type="tel"
                                     value={phone}
                                     onChange={(e) => setPhone(e.target.value)}
                                     className="w-full px-5 py-4 bg-gray-50 border border-gray-200 rounded-2xl focus:bg-white focus:border-[#15803d] focus:ring-4 focus:ring-green-100 outline-none transition-all text-gray-800 font-medium"
                                     placeholder="(00) 00000-0000"
                                     required
                                 />
                             </div>
                        </div>

                        {/* 5. Senha */}
                        <div>
                            <label className="block text-sm font-semibold text-gray-700 mb-2 ml-1">Senha</label>
                            {/* 💡 Wrapper para o ícone */}
                            <div className="relative"> 
                                <input
                                    // 💡 Tipo dinâmico
                                    type={showPassword ? 'text' : 'password'}
                                    placeholder="Mínimo 8 caracteres"
                                    value={password}
                                    onChange={(e) => setPassword(e.target.value)}
                                    required
                                    className="w-full pr-14 px-5 py-4 bg-gray-50 border border-gray-200 rounded-2xl focus:bg-white focus:border-[#15803d] focus:ring-4 focus:ring-green-100 outline-none transition-all text-gray-800 font-medium"
                                />
                                {/* 💡 Botão do toggle */}
                                <EyeIcon 
                                    isVisible={showPassword} 
                                    onClick={() => setShowPassword(!showPassword)} 
                                />
                            </div>
                        </div>

                        {/* 6. Confirmar Senha */}
                        <div>
                            <label className="block text-sm font-semibold text-gray-700 mb-2 ml-1">Confirmar Senha</label>
                            {/* 💡 Wrapper para o ícone */}
                            <div className="relative"> 
                                <input
                                    // 💡 Tipo dinâmico
                                    type={showConfirmationPassword ? 'text' : 'password'}
                                    placeholder="Repita a senha"
                                    value={passwordConfirmation}
                                    onChange={(e) => setPasswordConfirmation(e.target.value)}
                                    required
                                    className="w-full pr-14 px-5 py-4 bg-gray-50 border border-gray-200 rounded-2xl focus:bg-white focus:border-[#15803d] focus:ring-4 focus:ring-green-100 outline-none transition-all text-gray-800 font-medium"
                                />
                                {/* 💡 Botão do toggle */}
                                <EyeIcon 
                                    isVisible={showConfirmationPassword} 
                                    onClick={() => setShowConfirmationPassword(!showConfirmationPassword)} 
                                />
                            </div>
                        </div>
                        
                        <button
                            type="submit"
                            // 🟢 Desabilita o botão também quando for um sucesso
                            disabled={isProcessing || isCoursesLoading || isSuccess}
                            className={`w-full py-4 rounded-2xl text-white font-bold text-lg transition duration-300 flex items-center justify-center mt-6 shadow-lg shadow-green-700/20 hover:shadow-green-700/40 active:scale-[0.98] ${
                                isProcessing || isCoursesLoading || isSuccess ? 'bg-gray-400 cursor-not-allowed' : 'bg-[#15803d] hover:bg-[#166534]'
                            }`}
                        >
                            {isProcessing ? (
                                <>
                                    <LoaderCircle className="w-5 h-5 mr-2 text-white" />
                                    A Cadastrar...
                                </>
                            ) : isSuccess ? ( // 🟢 Mostra mensagem no botão após sucesso
                                'Redirecionando...'
                            ) : (
                                'Criar minha conta'
                            )}
                        </button>
                    </form>

                    <p className="mt-10 text-center text-gray-600">
                        Já tem uma conta? 
                        {/* CORREÇÃO: Usando a tag <a> com router.push explícito para garantir a navegação simulada */}
                        <a 
                            href="#" 
                            onClick={(e) => {
                                e.preventDefault(); // Impede a ação padrão da âncora
                                router.push('/login'); // Usa o router real (simulado) para o redirecionamento
                            }}
                            className="text-[#15803d] hover:underline hover:text-[#166534] font-bold ml-1"
                        >
                            Fazer Login
                        </a>
                    </p>
                </div>
            </div>
        </div>
    );
}