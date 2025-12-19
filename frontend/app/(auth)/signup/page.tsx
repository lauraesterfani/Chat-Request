'use client'; 

import React, { useState, useEffect, useCallback } from 'react';
import axios from 'axios'; 
import { useRouter } from 'next/navigation'; // 👈 IMPORT REAL (Para funcionar o push)
import Link from 'next/link';               // 👈 IMPORT REAL
import Image from 'next/image';             // 👈 IMPORT REAL

// --------------------------------------------------------------------
// 🎯 INTERFACES
// --------------------------------------------------------------------
interface Course {
    id: string; 
    name: string;
}

// 💡 HOOK useAuth (Mantém a lógica local por enquanto)
const useAuth = () => {
    const REGISTER_URL = 'http://127.0.0.1:8000/api/register';

    const register = useCallback(async (userData: any) => {
        console.log("[API REAL] Tentativa de Registo com:", userData);
        try {
            const response = await axios.post(REGISTER_URL, userData);
            return { 
                success: true, 
                message: response.data.message || "Registo bem-sucedido.",
                data: response.data 
            };
        } catch (error: any) {
            if (axios.isAxiosError(error) && error.response) {
                const errorMessage = error.response.data.message || 
                                     (typeof error.response.data === 'string' ? error.response.data : "Erro desconhecido no servidor.");
                return { success: false, message: errorMessage };
            }
            console.error("Erro na comunicação com a API:", error);
            return { 
                success: false, 
                message: "Ocorreu um erro inesperado na comunicação. Verifique se o Laravel está ativo (porta 8000)." 
            };
        }
    }, []);

    // Se você já tiver o AuthContext global funcionando, pode remover essa parte e usar o global.
    const isAuthenticated = false; 
    const isLoading = false; 

    return { register, isAuthenticated, isLoading };
};

// ... Componentes de UI (LoaderCircle, EyeIcon) ...
const LoaderCircle = (props: React.SVGProps<SVGSVGElement>) => (
    <svg {...props} xmlns="http://www.w3.org/2000/svg" width="24" height="24" 
        viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" 
        strokeLinecap="round" strokeLinejoin="round" className="animate-spin">
        <path d="M21 12a9 9 0 1 1-6.219-8.56" />
    </svg>
);

const EyeIcon = (props: React.SVGProps<SVGSVGElement> & { isVisible: boolean, onClick: () => void }) => (
    <button 
        type="button" 
        onClick={props.onClick} 
        className="p-2 text-gray-400 hover:text-gray-600 transition duration-150 absolute right-2 top-1/2 -translate-y-1/2 focus:outline-none focus:ring-2 focus:ring-[#15803d] rounded-full"
        aria-label={props.isVisible ? "Ocultar senha" : "Visualizar senha"}
    >
        {props.isVisible ? (
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24" />
                <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68" />
                <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.48-1.6" />
                <line x1="2" x2="22" y1="2" y2="22" />
            </svg>
        ) : (
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z" />
                <circle cx="12" cy="12" r="3" />
            </svg>
        )}
    </button>
);


export default function SignupPage() {

    const router = useRouter(); // 👈 AGORA É O ROUTER REAL
    const { register, isAuthenticated, isLoading } = useAuth();
    
    // Campos de cadastro
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
    
    // 🟠 ESTADOS DE ERRO E CARREGAMENTO
    const [error, setError] = useState<string | null>(null); 
    const [isProcessing, setIsProcessing] = useState(false);
    const [isCoursesLoading, setIsCoursesLoading] = useState(false); 

    // ✅ REGRAS DE VALIDAÇÃO DE SENHA (Atualizado conforme Backend)
    const requirements = [
        { id: 1, text: "Mínimo de 8 caracteres", met: password.length >= 8 },
        { id: 2, text: "Letra maiúscula e minúscula", met: /[a-z]/.test(password) && /[A-Z]/.test(password) },
        { id: 3, text: "Pelo menos um número", met: /[0-9]/.test(password) },
        { id: 4, text: "Pelo menos um símbolo (ex: !@#)", met: /[^A-Za-z0-9]/.test(password) },
    ];

    // 1. Redirecionamento após autenticação
    useEffect(() => {
        if (!isLoading && isAuthenticated) {
            router.push('/me'); 
        }
    }, [isAuthenticated, isLoading, router]);

    // 2. Carregar Cursos do Backend
    useEffect(() => {
        const COURSES_URL = 'http://127.0.0.1:8000/api/courses';
        
        const fetchCourses = async () => {
            setIsCoursesLoading(true); 
            setError(null); 

            try {
                const response = await axios.get(COURSES_URL);
                setCourses(response.data); 
                
            } catch (error: any) {
                console.error("[API REAL] Erro ao carregar cursos:", error);
                let errorMessage = "Erro ao carregar cursos. Verifique se o Laravel está ativo e a rota '/api/courses' existe.";
                if (axios.isAxiosError(error) && error.response) {
                    errorMessage = error.response.data.message || errorMessage;
                }
                setError(errorMessage); 
            } finally {
                setIsCoursesLoading(false);
            }
        };
        fetchCourses();
    }, []);

    // 3. Redirecionamento após cadastro
    useEffect(() => {
        if (isSuccess) {
            setError("Conta criada com sucesso! Redirecionando para a página de Login..."); 

            const timer = setTimeout(() => {
                router.push('/login'); // 👈 AGORA VAI NAVEGAR DE VERDADE
            }, 3000); 
            
            return () => clearTimeout(timer); 
        }
    }, [isSuccess, router]);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setError(null);
        setIsSuccess(false);

        if (password !== passwordConfirmation) {
            setError("As senhas não correspondem.");
            return;
        }
        
        if (!name || !cpf || !email || !password || !phone || !birthday || !matricula || !courseId) {
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
                setIsSuccess(true); 
            } else {
                setError(result.message || "Erro ao realizar cadastro.");
            }

        } catch (err) {
            setError("Ocorreu um erro inesperado. Verifique o console para mais detalhes.");
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
                          width={256}  // 👈 Next/Image precisa de width/height
                          height={256}
                          unoptimized // 👈 Importante para URLs externas sem config
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

                    {error && (
                      <div className={`mb-8 p-4 border rounded-2xl flex items-start gap-3 ${
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

                        {/* 2. Grid Matrícula + Curso */}
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
                              ) : courses.length === 0 ? (
                                <div className="w-full px-5 py-4 bg-red-50 border border-red-200 rounded-2xl text-red-700 font-medium">
                                    {error || "Nenhum curso disponível. Verifique a API."}
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

                        {/* 3. Grid CPF + Data */}
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

                        {/* 5. Senha com Requisitos Visuais */}
                        <div>
                            <label className="block text-sm font-semibold text-gray-700 mb-2 ml-1">Senha</label>
                            <div className="relative"> 
                                <input
                                    type={showPassword ? 'text' : 'password'}
                                    placeholder="Mínimo 8 caracteres"
                                    value={password}
                                    onChange={(e) => setPassword(e.target.value)}
                                    required
                                    className="w-full pr-14 px-5 py-4 bg-gray-50 border border-gray-200 rounded-2xl focus:bg-white focus:border-[#15803d] focus:ring-4 focus:ring-green-100 outline-none transition-all text-gray-800 font-medium"
                                />
                                <EyeIcon 
                                    isVisible={showPassword} 
                                    onClick={() => setShowPassword(!showPassword)} 
                                />
                            </div>
                            
                            {/* --- LISTA DE REQUISITOS --- */}
                            <div className="mt-3 p-3 bg-gray-50 rounded-lg border border-gray-100">
                                <p className="text-xs font-semibold text-gray-500 mb-2">
                                    Sua senha deve conter:
                                </p>
                                <ul className="space-y-1">
                                    {requirements.map((req) => (
                                    <li 
                                        key={req.id} 
                                        className={`text-xs flex items-center gap-2 transition-colors duration-200 ${
                                        req.met ? "text-green-600 font-medium" : "text-gray-400"
                                        }`}
                                    >
                                        {req.met ? (
                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        ) : (
                                        <span className="w-1.5 h-1.5 rounded-full bg-gray-300 ml-1.5 mr-1" />
                                        )}
                                        {req.text}
                                    </li>
                                    ))}
                                </ul>
                            </div>
                        </div>

                        {/* 6. Confirmar Senha */}
                        <div>
                            <label className="block text-sm font-semibold text-gray-700 mb-2 ml-1">Confirmar Senha</label>
                            <div className="relative"> 
                                <input
                                    type={showConfirmationPassword ? 'text' : 'password'}
                                    placeholder="Repita a senha"
                                    value={passwordConfirmation}
                                    onChange={(e) => setPasswordConfirmation(e.target.value)}
                                    required
                                    className="w-full pr-14 px-5 py-4 bg-gray-50 border border-gray-200 rounded-2xl focus:bg-white focus:border-[#15803d] focus:ring-4 focus:ring-green-100 outline-none transition-all text-gray-800 font-medium"
                                />
                                <EyeIcon 
                                    isVisible={showConfirmationPassword} 
                                    onClick={() => setShowConfirmationPassword(!showConfirmationPassword)} 
                                />
                            </div>
                        </div>
                        
                        <button
                            type="submit"
                            disabled={isProcessing || isCoursesLoading || isSuccess || courses.length === 0}
                            className={`w-full py-4 rounded-2xl text-white font-bold text-lg transition duration-300 flex items-center justify-center mt-6 shadow-lg shadow-green-700/20 hover:shadow-green-700/40 active:scale-[0.98] ${
                                isProcessing || isCoursesLoading || isSuccess || courses.length === 0 ? 'bg-gray-400 cursor-not-allowed' : 'bg-[#15803d] hover:bg-[#166534]'
                            }`}
                        >
                            {isProcessing ? (
                                <>
                                    <LoaderCircle className="w-5 h-5 mr-2 text-white" />
                                    A Cadastrar...
                                </>
                            ) : isSuccess ? ( 
                                'Redirecionando...'
                            ) : (
                                'Criar minha conta'
                            )}
                        </button>
                    </form>

                    <p className="mt-10 text-center text-gray-600">
                        Já tem uma conta? 
                        <Link 
                            href="/login" // 👈 LINK REAL (Usa o Link do Next.js)
                            className="text-[#15803d] hover:underline hover:text-[#166534] font-bold ml-1"
                        >
                            Fazer Login
                        </Link>
                    </p>
                </div>
            </div>
        </div>
    );
}