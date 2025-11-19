import React, { CSSProperties } from 'react';

export default function Page() {
  // APLICAÇÃO DA TIPAGEM (Corrigida para evitar erros de TypeScript)
  const containerStyle: CSSProperties = {
    padding: '0 5%',
    fontFamily: 'Arial, sans-serif',
    color: '#333',
  };

  const headerStyle: CSSProperties = {
    display: 'flex',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: '10px 0',
    borderBottom: '4px solid #007bff',
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

  // Estilo genérico para links de navegação
  const navLinkStyle: CSSProperties = {
    textDecoration: 'none',
    color: '#333',
    fontSize: '14px',
    padding: '8px 0',
  };

  // Estilo específico para o botão de acesso (Menu)
  const accessButtonStyle: CSSProperties = {
    ...navLinkStyle, // Herdando base do link
    backgroundColor: '#007bff',
    color: 'white',
    padding: '8px 15px',
    borderRadius: '5px',
    fontWeight: 'bold',
  };

  const heroSectionStyle: CSSProperties = {
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'space-between',
    padding: '80px 0',
    minHeight: '400px',
  };

  const textContentStyle: CSSProperties = {
    maxWidth: '500px',
  };

  const titleStyle: CSSProperties = {
    fontSize: '40px',
    fontWeight: 'bold',
    color: '#333',
    lineHeight: '1.2',
    marginBottom: '20px',
  };

  const subtitleStyle: CSSProperties = {
    fontSize: '16px',
    color: '#666',
    marginBottom: '30px',
  };

  const buttonContainerStyle: CSSProperties = {
    display: 'flex',
    gap: '15px',
  };

  // Estilo específico para o botão primário (Hero Section)
  const primaryButtonStyle: CSSProperties = {
    backgroundColor: '#28a745',
    color: 'white',
    padding: '10px 20px',
    borderRadius: '5px',
    textDecoration: 'none',
    fontWeight: 'bold',
    border: 'none',
    cursor: 'pointer',
    display: 'inline-block', // Para que 'a' se comporte como 'button'
  };

  // Estilo específico para o botão secundário (Hero Section)
  const secondaryButtonStyle: CSSProperties = {
    backgroundColor: 'white',
    color: '#333',
    padding: '10px 20px',
    borderRadius: '5px',
    textDecoration: 'none',
    fontWeight: 'bold',
    border: '1px solid #ccc',
    cursor: 'pointer',
    display: 'inline-block', // Para que 'a' se comporte como 'button'
  };

  // ESTILO QUE ESTAVA CAUSANDO O ERRO DE TIPO NA DIV
  const sectionDividerStyle: CSSProperties = {
    textAlign: 'center',
    padding: '50px 0 80px 0',
  };

  const sectionTitleStyle: CSSProperties = {
    fontSize: '28px',
    fontWeight: 'bold',
    color: '#333',
    marginBottom: '10px',
  };

  const sectionSubtitleStyle: CSSProperties = {
    fontSize: '18px',
    color: '#666',
  };

  // Simulação dos placeholders de imagem (para fins de estrutura)

  const laptopPlaceholder = (
    <div style={{ width: '250px', height: '180px', border: '1px solid #ccc', background: '#f8f8f8', display: 'flex', flexDirection: 'column', justifyContent: 'space-around', alignItems: 'center', padding: '10px' }}>
        <div style={{width: '90%', height: '20px', background: 'lightgray'}}></div>
        <div style={{width: '90%', height: '20px', background: 'lightgray'}}></div>
        <div style={{width: '90%', height: '20px', background: 'lightgray'}}></div>
        <p style={{fontSize: '12px', color: '#666'}}>Laptop Mockup</p>
    </div>
  );
  
  const phonePlaceholder = (
    <div style={{ width: '80px', height: '180px', border: '1px solid #ccc', background: '#f8f8f8', borderRadius: '10px', display: 'flex', justifyContent: 'center', alignItems: 'center' }}>
        <div style={{width: '60px', height: '100px', background: 'lightgray'}}></div>
    </div>
  );
  
  // Combinação dos placeholders
  const imagePlaceholderStyle: CSSProperties = { // <-- Tipagem corrigida
    display: 'flex',
    gap: '20px',
  };
  

  return (
    <div style={containerStyle}>
      {/* 1. Header (Menu) */}
      <header style={headerStyle}>
        <div style={logoStyle}>
          {/* Ícone de tartaruga ou similar */}
          <span role="img" aria-label="Turtle icon" style={{marginRight: '5px'}}>🐢</span>
          Chat Request
        </div>
        <nav style={navStyle}>
          {/* ROTA DO MENU: Funcionalidades */}
          <a href="/funcionalidades" style={navLinkStyle}>Funcionalidades</a>
          {/* ROTA DO MENU: Como Funciona (Aponta para o arquivo que criamos) */}
          <a href="/como-funciona" style={navLinkStyle}>Como Funciona</a>
          {/* ROTA DO MENU: Acesso CRADT */}
          <a href="/cradt" style={accessButtonStyle}>Acesso CRADT</a>
        </nav>
      </header>

      {/* 2. Seção Hero (Conteúdo Principal) */}
      <div style={heroSectionStyle}>
        <div style={textContentStyle}>
          <h1 style={titleStyle}>
            Gerencie Seus Requerimentos <br />
            de **Forma Simples e Rápida**
          </h1>
          <p style={subtitleStyle}>
            Com o Chat Request, você solicita e acompanha seus requerimentos acadêmicos através de um chat inteligente, sem burocracia.
          </p>
          <div style={buttonContainerStyle}>
            {/* ROTA PRINCIPAL: Acessar sua Conta */}
            <a href="/login" style={primaryButtonStyle}>Acessar sua Conta</a>
            {/* ROTA PRINCIPAL: Cadastre-se */}
            <a href="/signup" style={secondaryButtonStyle}>Cadastre-se</a>
          </div>
        </div>

        {/* Placeholder das Imagens (Laptop e Celular) */}
        <div style={imagePlaceholderStyle}>
          {laptopPlaceholder}
          {phonePlaceholder}
        </div>
      </div>
      
      <hr style={{ border: 'none', margin: '40px 0' }} />

      {/* 3. Seção Inferior (Div que estava dando erro) */}
      <div style={sectionDividerStyle}>
        <h2 style={sectionTitleStyle}>
          Tudo que você precisa em um só lugar
        </h2>
        <p style={sectionSubtitleStyle}>
          Simplifique sua vida acadêmica com nossas ferramentas.
        </p>
      </div>
    </div>
  );
}