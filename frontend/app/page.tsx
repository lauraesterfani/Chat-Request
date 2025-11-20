import Image from "next/image";
import Link from "next/link";

export default function LandingPage() {
  return (
    <div className="min-h-screen bg-white font-sans selection:bg-green-100">
      
      {/* =========================================
          NAVBAR
      ========================================= */}
      <nav className="w-full max-w-[1440px] mx-auto px-6 py-6 flex items-center justify-between sticky top-0 bg-white/90 backdrop-blur-md z-50">
        
        {/* 1. Logo */}
        <div className="flex items-center gap-2">
          <div className="w-8 h-8 bg-[#15803d] rounded flex items-center justify-center">
             <span className="text-white font-bold text-sm">CR</span>
          </div>
          <span className="text-xl font-bold text-[#0f172a] tracking-tight">Chat Request</span>
        </div>

        {/* 2. Links Centrais */}
        <div className="hidden md:flex items-center gap-10 text-sm font-medium text-gray-500">
          <Link href="#" className="text-[#15803d] font-semibold">Início</Link>
          <Link href="#como-funciona" className="hover:text-[#15803d] transition-colors">Como funciona</Link>
          <Link href="#cradt" className="hover:text-[#15803d] transition-colors">Acesso CRADT</Link>
        </div>

        {/* 3. Botões da Direita */}
        <div className="flex items-center gap-4">
          <Link href="/login" className="text-sm font-semibold text-gray-600 hover:text-[#15803d] transition-colors">
            Entrar
          </Link>
          <Link href="/signup" className="bg-[#15803d] text-white text-sm font-semibold px-6 py-2.5 rounded-full hover:bg-[#166534] transition-colors shadow-sm">
            Criar conta
          </Link>
        </div>
      </nav>


      {/* =========================================
          HERO SECTION
      ========================================= */}
      <header className="w-full max-w-[1440px] mx-auto px-6 pt-8 pb-20 md:pt-16 md:pb-32 flex flex-col md:flex-row items-center">
        
        {/* Texto */}
        <div className="w-full md:w-1/2 flex flex-col items-start space-y-8 pr-0 md:pr-12 animate-fade-in-up">
          <div className="bg-[#dcfce7] text-[#15803d] px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wide">
            Simplifique sua vida acadêmica
          </div>

          <h1 className="text-5xl md:text-[3.5rem] leading-[1.1] font-bold text-[#0f172a]">
            Gerencie seus <br />
            <span className="text-[#15803d]">Requerimentos</span> <br />
            de forma fácil.
          </h1>

          <p className="text-lg text-gray-500 leading-relaxed max-w-md">
            Uma plataforma intuitiva para alunos e coordenadores. Envie documentos, acompanhe status e resolva pendências sem filas.
          </p>

          <div className="flex flex-row gap-4 pt-2">
            <Link href="/signup" className="bg-[#15803d] text-white text-base font-semibold px-8 py-3 rounded-full hover:bg-[#166534] transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
              Começar agora
            </Link>
            <Link href="#como-funciona" className="border border-[#15803d] text-[#15803d] text-base font-semibold px-8 py-3 rounded-full hover:bg-[#f0fdf4] transition-colors">
              Saiba mais
            </Link>
          </div>
        </div>

        {/* Imagem */}
        <div className="w-full md:w-1/2 relative flex justify-center mt-16 md:mt-0">
            <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[400px] h-[400px] bg-[#dcfce7] rounded-full blur-3xl -z-10 opacity-60"></div>
            
            <div className="relative w-[450px] h-[450px]">
                 <Image 
                  src="/mascote.png" 
                  alt="Estudante Chat Request" 
                  fill
                  className="object-contain drop-shadow-2xl"
                  priority
                />
                
                {/* Cards Flutuantes */}
                <div className="absolute top-10 left-0 bg-white p-3 rounded-2xl shadow-lg animate-bounce duration-[3000ms]">
                    <span className="text-2xl">👍</span>
                </div>
                <div className="absolute bottom-20 -left-4 bg-white p-3 rounded-2xl shadow-lg animate-pulse">
                    <span className="text-2xl">💬</span>
                </div>
                 <div className="absolute top-20 right-4 bg-white p-3 rounded-2xl shadow-lg">
                    <div className="bg-green-100 text-green-600 rounded-full p-1">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={3} stroke="currentColor" className="w-5 h-5">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>
      </header>


      {/* =========================================
          SECTION: COMO FUNCIONA
      ========================================= */}
      <section id="como-funciona" className="w-full bg-[#f8fafc] py-24 px-6 rounded-t-[3rem]">
        <div className="max-w-[1440px] mx-auto">
            
            <div className="text-center mb-16 max-w-2xl mx-auto">
                <h2 className="text-3xl md:text-4xl font-bold text-[#0f172a] mb-4">
                    Como funciona?
                </h2>
                <p className="text-gray-500 text-lg">
                    Entenda como o Chat Request agiliza seus processos em apenas três passos simples.
                </p>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-8 md:gap-12">
                {/* Card 1 */}
                <div className="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 flex flex-col items-center text-center hover:shadow-md transition-shadow">
                    <div className="w-16 h-16 bg-[#dcfce7] rounded-full flex items-center justify-center mb-6 text-[#15803d]">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" className="w-8 h-8">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                        </svg>
                    </div>
                    <h3 className="text-xl font-bold text-[#0f172a] mb-3">Abra o requerimento</h3>
                    <p className="text-gray-500 leading-relaxed">
                        É tudo digital! Selecione o tipo de solicitação, anexe os documentos necessários e envie na hora.
                    </p>
                </div>

                {/* Card 2 */}
                <div className="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 flex flex-col items-center text-center hover:shadow-md transition-shadow">
                    <div className="w-16 h-16 bg-[#dcfce7] rounded-full flex items-center justify-center mb-6 text-[#15803d]">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" className="w-8 h-8">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.18.063-2.33.12-3.45.164m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0011.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />
                        </svg>
                    </div>
                    <h3 className="text-xl font-bold text-[#0f172a] mb-3">Acompanhe pelo Chat</h3>
                    <p className="text-gray-500 leading-relaxed">
                        Tire dúvidas diretamente com a secretaria através de um chat exclusivo integrado ao seu pedido.
                    </p>
                </div>

                {/* Card 3 */}
                <div className="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 flex flex-col items-center text-center hover:shadow-md transition-shadow">
                    <div className="w-16 h-16 bg-[#dcfce7] rounded-full flex items-center justify-center mb-6 text-[#15803d]">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" className="w-8 h-8">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                        </svg>
                    </div>
                    <h3 className="text-xl font-bold text-[#0f172a] mb-3">Receba a Atualização</h3>
                    <p className="text-gray-500 leading-relaxed">
                        Seja notificado em tempo real sobre o deferimento ou correções necessárias no seu processo.
                    </p>
                </div>
            </div>
        </div>
      </section>


      {/* =========================================
          SECTION AZUL: ACESSO CRADT / FOOTER
          (Baseado na Home 3.png)
      ========================================= */}
      <footer id="cradt" className="bg-[#0f172a] text-white pt-20 pb-10 px-6 rounded-t-[3rem] -mt-8 relative z-10">
        <div className="max-w-[1440px] mx-auto flex flex-col md:flex-row justify-between items-start gap-12">
           
           {/* Coluna 1: Marca e Descrição */}
           <div className="md:w-1/3 space-y-6">
                <div className="flex items-center gap-3">
                    <div className="w-10 h-10 bg-[#15803d] rounded flex items-center justify-center">
                        <span className="text-white font-bold text-lg">CR</span>
                    </div>
                    <span className="text-2xl font-bold tracking-tight">Chat Request</span>
                </div>
                <p className="text-gray-400 leading-relaxed">
                    Modernizando a comunicação acadêmica. Uma solução completa para facilitar a vida de alunos e servidores.
                </p>
           </div>

           {/* Coluna 2: Links Rápidos */}
           <div className="grid grid-cols-2 gap-16">
                <div>
                    <h4 className="font-bold text-lg mb-6 text-white">Plataforma</h4>
                    <ul className="space-y-3 text-gray-400">
                        <li><Link href="/login" className="hover:text-[#15803d] transition-colors">Login Aluno</Link></li>
                        <li><Link href="/signup" className="hover:text-[#15803d] transition-colors">Cadastro</Link></li>
                        <li><Link href="#como-funciona" className="hover:text-[#15803d] transition-colors">Funcionalidades</Link></li>
                    </ul>
                </div>

                {/* Coluna 3: Acesso Administrativo (CRADT) */}
                <div>
                    <h4 className="font-bold text-lg mb-6 text-white">Área Restrita</h4>
                    <ul className="space-y-3 text-gray-400">
                        <li className="flex flex-col gap-2">
                            <span className="text-sm opacity-60">Acesso exclusivo para servidores</span>
                            <Link href="/cradt-login" className="inline-flex items-center gap-2 text-blue-300 hover:text-blue-100 transition-colors font-medium">
                                Acesso CRADT
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" className="w-4 h-4">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                </svg>
                            </Link>
                        </li>
                    </ul>
                </div>
           </div>
        </div>

        {/* Copyright / Bottom Footer */}
        <div className="max-w-[1440px] mx-auto mt-16 pt-8 border-t border-gray-800 flex flex-col md:flex-row justify-between items-center text-sm text-gray-500">
            <p>&copy; 2025 Chat Request. Todos os direitos reservados.</p>
            <div className="flex gap-6 mt-4 md:mt-0">
                <a href="#" className="hover:text-white transition-colors">Termos de Uso</a>
                <a href="#" className="hover:text-white transition-colors">Privacidade</a>
            </div>
        </div>
      </footer>

    </div>
  );
}