import React, { CSSProperties } from 'react';
import Link from 'next/link';

// =================================================================
// 1. ESTILOS BASE E HEADER
// =================================================================

const baseContainerStyle: CSSProperties = {
  padding: '0 5%',
  fontFamily: 'Arial, sans-serif',
  color: '#333',
  backgroundColor: 'white',
};

const headerStyle: CSSProperties = {
  display: 'flex',
  justifyContent: 'space-between',
  alignItems: 'center',
  padding: '10px 0',
  borderBottom: '4px solid #007bff', // Linha azul
};

const logoStyle: CSSProperties = {
  display: 'flex',
  alignItems: 'center',
  fontSize: '20px',
  fontWeight: 'bold',
  color: '#333',
};

const navStyle: CSSProperties = {
  display: 'flex',
  gap: '20px',
  alignItems: 'center',
};

const navLinkStyle: CSSProperties = {
  textDecoration: 'none',
  color: '#333',
  fontSize: '14px',
};

const accessButtonStyle: CSSProperties = {
  textDecoration: 'none',
  backgroundColor: '#007bff',
  color: 'white',
  padding: '8px 15px',
  borderRadius: '5px',
  fontSize: '14px',
  fontWeight: 'bold',
};

// =================================================================
// 2. ESTILOS DA SEÇÃO DE FUNCIONALIDADES (CARDS)
// =================================================================

const topSubtitleStyle: CSSProperties = {
    textAlign: 'center',
    fontSize: '18px',
    color: '#666',
    padding: '40px 0',
};

const featuresSectionStyle: CSSProperties = {
  display: 'flex',
  justifyContent: 'center',
  gap: '30px',
  padding: '20px 5% 60px',
  textAlign: 'center',
};

const featureCardStyle: CSSProperties = {
  maxWidth: '300px',
  padding: '30px 20px',
  borderRadius: '8px',
  border: '1px solid #eee',
  backgroundColor: 'white',
  boxShadow: '0 2px 4px rgba(0,0,0,0.05)',
  flex: '1', // Garante que ocupem espaço igual
};

const featureIconWrapperStyle: CSSProperties = {
  // Container do ícone
  display: 'inline-flex',
  width: '50px',
  height: '50px',
  justifyContent: 'center',
  alignItems: 'center',
  borderRadius: '8px',
  backgroundColor: '#e6f7e9', // Fundo verde claro para o ícone
  marginBottom: '15px',
};

const featureIconStyle: CSSProperties = {
  color: '#28a745', // Verde
  fontSize: '30px',
};

const featureTitleStyle: CSSProperties = {
  fontSize: '18px',
  fontWeight: 'bold',
  color: '#333',
  marginBottom: '10px',
};

const featureDescriptionStyle: CSSProperties = {
  fontSize: '14px',
  color: '#666',
  lineHeight: '1.4',
};

// =================================================================
// 3. ESTILOS DA SEÇÃO DE PASSOS ("COMO FUNCIONA?")
// =================================================================

const howItWorksTitleStyle: CSSProperties = {
    textAlign: 'center',
    padding: '40px 0 10px',
    fontSize: '32px',
    fontWeight: 'bold',
    color: '#333',
};

const howItWorksSubtitleStyle: CSSProperties = {
    textAlign: 'center',
    fontSize: '16px',
    color: '#666',
    marginBottom: '50px',
};

const stepsContainerStyle: CSSProperties = {
  display: 'flex',
  justifyContent: 'center',
  gap: '80px',
  paddingBottom: '80px',
  textAlign: 'center',
};

const stepItemStyle: CSSProperties = {
  maxWidth: '250px',
};

const stepNumberStyle: CSSProperties = {
  width: '50px',
  height: '50px',
  borderRadius: '50%',
  backgroundColor: '#28a745', // Verde
  color: 'white',
  display: 'flex',
  justifyContent: 'center',
  alignItems: 'center',
  fontSize: '24px',
  fontWeight: 'bold',
  margin: '0 auto 20px',
};

const stepTitleStyle: CSSProperties = {
  fontSize: '18px',
  fontWeight: 'bold',
  color: '#333',
  marginBottom: '10px',
};

const stepDescriptionStyle: CSSProperties = {
  fontSize: '14px',
  color: '#666',
};

// =================================================================
// COMPONENTE PRINCIPAL (EXPORTADO COMO Page)
// =================================================================

export default function Page() { // Renomeado de ComoFunciona para Page
  return (
    <div style={baseContainerStyle}>
      {/* 1. Header (Menu) */}
      <header style={headerStyle}>
        <div style={logoStyle}>
          {/* Rota para a página inicial */}
          <Link href="/" style={{...logoStyle, textDecoration: 'none'}}>
            <span role="img" aria-label="Turtle icon" style={{marginRight: '5px'}}>🐢</span>
            Chat Request
          </Link>
        </div>
        <nav style={navStyle}>
          <Link href="/servicos" style={navLinkStyle}>Serviços</Link>
          <Link href="/como-funciona" style={navLinkStyle}>Como Funciona</Link>
          <Link href="/cradt-login" style={accessButtonStyle}>Acesso CRADT</Link>
        </nav>
      </header>
      
      {/* Subtítulo - "Simplifique sua vida acadêmica com nossas ferramentas." */}
      <p style={topSubtitleStyle}>
        Simplifique sua vida acadêmica com nossas ferramentas.
      </p>

      {/* 2. Seção de Funcionalidades (Três Cards) */}
      <div style={featuresSectionStyle}>
        
        {/* Card 1: Chat Inteligente */}
        <div style={featureCardStyle}>
          <div style={featureIconWrapperStyle}>
            <span style={featureIconStyle}>💬</span> 
          </div>
          <h3 style={featureTitleStyle}>Chat Inteligente</h3>
          <p style={featureDescriptionStyle}>
            Solicite seus requerimentos através de uma conversa guiada, rápida e intuitiva.
          </p>
        </div>

        {/* Card 2: Anexo de Documentos */}
        <div style={featureCardStyle}>
          <div style={featureIconWrapperStyle}>
            <span style={featureIconStyle}>📄</span> 
          </div>
          <h3 style={featureTitleStyle}>Anexo de Documentos</h3>
          <p style={featureDescriptionStyle}>
            Envie atestados, comprovantes e outros arquivos necessários diretamente pelo chat.
          </p>
        </div>

        {/* Card 3: Acompanhamento de Status */}
        <div style={featureCardStyle}>
          <div style={featureIconWrapperStyle}>
            <span style={{...featureIconStyle, color: '#28a745'}}>✅</span> 
          </div>
          <h3 style={featureTitleStyle}>Acompanhamento de Status</h3>
          <p style={featureDescriptionStyle}>
            Veja em tempo real se seu pedido foi aberto, está em análise, foi deferido ou indeferido.
          </p>
        </div>
      </div>

      {/* 3. Seção Como Funciona? (Três Passos) */}
      
      <h2 style={howItWorksTitleStyle}>Como Funciona?</h2>
      <p style={howItWorksSubtitleStyle}>
        Em poucos passos, seu requerimento está a caminho
      </p>

      <div style={stepsContainerStyle}>
        
        {/* Passo 1: Acesse sua Conta */}
        <div style={stepItemStyle}>
          <div style={stepNumberStyle}>1</div>
          <h3 style={stepTitleStyle}>Acesse sua Conta</h3>
          <p style={stepDescriptionStyle}>
            Acesse com sua matrícula institucional e senha fornecida pelo campus
          </p>
        </div>

        {/* Passo 2: Inicie o Chat */}
        <div style={stepItemStyle}>
          <div style={stepNumberStyle}>2</div>
          <h3 style={stepTitleStyle}>Inicie o Chat</h3>
          <p style={stepDescriptionStyle}>
            Escolha &quot;Novo Requerimento&quot; e siga as instruções do nosso assistente
          </p>
        </div>

        {/* Passo 3: Acompanhe */}
        <div style={stepItemStyle}>
          <div style={stepNumberStyle}>3</div>
          <h3 style={stepTitleStyle}>Acompanhe</h3>
          <p style={stepDescriptionStyle}>
            Consulte o status de todos os seus pedidos em um painel organizado
          </p>
        </div>
      </div>
      
    </div>
  );
}