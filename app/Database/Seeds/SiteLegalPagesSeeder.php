<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use App\Database\Seeds\Concerns\IdempotentSeederSupport;
use CodeIgniter\Database\Seeder;

/**
 * Seeds legal, policy, transparency, data protection, and accessibility pages.
 * Fully multi-language (es/en) and idempotent.
 */
class SiteLegalPagesSeeder extends Seeder
{
    use IdempotentSeederSupport;

    public function run(): void
    {
        // PHASE 0: Auto-migrate old legal page slugs if they exist (from prior runs)
        $this->migrateOldLegalPages();

        $langIds = $this->langIds(['es', 'en']);
        if (! isset($langIds['es'], $langIds['en'])) {
            echo "SiteLegalPagesSeeder: missing languages. Seed CmsLanguageSeeder first.\n";
            return;
        }

        $blockIds = $this->blockIds([
            'page_header', 'rich_text', 'anchor_nav', 'faq_accordion', 'document_gallery', 'form_embed'
        ]);

        $now = date('Y-m-d H:i:s');

        // ── 1. Aviso Legal (legal-notice) ─────────────────────────────────────
        $legalNoticeId = $this->upsertLegalPage('aviso-legal', 'generic', [
            'status'             => 'published',
            'published_at'       => $now,
            'scheduled_at'       => null,
            'sort_order'         => 100,
            'sitemap_priority'   => '0.3',
            'sitemap_changefreq' => 'monthly',
            'is_in_sitemap'      => 1,
        ]);
        $this->upsertPageTranslation($legalNoticeId, $langIds['es'], [
            'slug'             => 'aviso-legal',
            'title'            => 'Aviso Legal',
            'excerpt'          => 'Información legal obligatoria sobre el propietario del sitio web.',
            'meta_title'       => 'Aviso Legal | Mi Sitio',
            'meta_description' => 'Aviso legal e identificación del propietario del sitio web.',
            'canonical_url'    => null,
            'robots'           => 'index, follow',
            'schema_data'      => null,
        ]);
        $this->upsertPageTranslation($legalNoticeId, $langIds['en'], [
            'slug'             => 'legal-notice',
            'title'            => 'Legal Notice',
            'excerpt'          => 'Mandatory legal information about the website owner.',
            'meta_title'       => 'Legal Notice | My Site',
            'meta_description' => 'Legal notice and website owner details.',
            'canonical_url'    => null,
            'robots'           => 'index, follow',
            'schema_data'      => null,
        ]);

        $this->resetPageBlocks($legalNoticeId);
        $this->upsertBlock($legalNoticeId, $blockIds, 'page_header', 1, [], [
            'es' => ['heading' => 'Aviso Legal', 'subheading' => 'Información de cumplimiento sobre el propietario y operación del sitio web.'],
            'en' => ['heading' => 'Legal Notice', 'subheading' => 'Complete compliance information regarding website ownership and operation.']
        ], $langIds);
        $this->upsertBlock($legalNoticeId, $blockIds, 'rich_text', 2, [], [
            'es' => ['content' => '<h2>1. Datos Identificativos del Responsable</h2><p>En cumplimiento con la Ley de Servicios de la Sociedad de la Información (LSSI-CE) y el Reglamento General de Protección de Datos (GDPR 2016/679), se pone a disposición del usuario la siguiente información identificativa:</p><ul><li><strong>Denominación Social:</strong> [TU_NOMBRE_EMPRESA]</li><li><strong>Domicilio:</strong> [TU_DIRECCIÓN_COMPLETA]</li><li><strong>Número de Identificación Fiscal (NIF):</strong> [TU_NIF_CIF_REAL]</li><li><strong>Número de Registro Mercantil:</strong> [TU_REGISTRO_MERCANTIL_COMPLETO]</li><li><strong>Representante Legal:</strong> [Nombre y cargo del administrador]</li><li><strong>Correo de Contacto:</strong> [TU_EMAIL_LEGAL]</li></ul><h2>2. Responsable del Tratamiento de Datos</h2><p>La empresa responsable del tratamiento de datos personales en este sitio es <strong>[TU_NOMBRE_EMPRESA]</strong> (la "Empresa"), actuando como responsable del tratamiento conforme al artículo 4.7 del GDPR.</p><h2>3. Derechos y Obligaciones del Usuario</h2><p>El acceso y utilización de este sitio web atribuye al usuario la condición de USUARIO, quien acepta de forma íntegra todas las disposiciones incluidas en este Aviso Legal y en el resto de políticas aplicables. El usuario se compromete a utilizar el sitio de conformidad con la ley y a no infringir los derechos de terceros.</p><h2>4. Propiedad Intelectual e Industrial</h2><p>Todos los contenidos, diseños, textos, imágenes, marcas, logotipos, iconos, botones, software, código fuente y demás elementos del sitio son propiedad de [TU_NOMBRE_EMPRESA] o de sus licenciantes. Están protegidos por las leyes de propiedad intelectual e industrial. Queda expresamente prohibido cualquier uso no autorizado, reproducción, distribución, comunicación pública o transformación de estos contenidos sin consentimiento previo por escrito.</p><h2>5. Exclusión de Responsabilidad</h2><p>[TU_NOMBRE_EMPRESA] no garantiza la continuidad, disponibilidad, exactitud o corrección del sitio web. El usuario utiliza el sitio bajo su propia responsabilidad. La empresa no será responsable de:</p><ul><li>Interrupciones o pausas en la disponibilidad del servicio</li><li>Errores técnicos o defectos en la información publicada</li><li>Daños y perjuicios derivados del acceso, uso o no acceso a los contenidos</li><li>Virus, malware o cualquier ataque cibernético sufrido</li><li>Enlaces a terceros o contenido externo disponible en este sitio</li></ul><h2>6. Limitación de Responsabilidad</h2><p>En ningún caso [TU_NOMBRE_EMPRESA] será responsable de daños directos, indirectos, incidentales, especiales, ejemplares o consecuentes derivados del uso o imposibilidad de uso del sitio, incluso si ha sido notificada de la posibilidad de tales daños.</p><h2>7. Ley Aplicable y Jurisdicción</h2><p>Este Aviso Legal se rige por la ley española, específicamente por la Ley 34/1988, de 11 de noviembre, de Ordenación del Sector Público Anunciante, y la LSSI-CE. Para cualquier controversia, las partes se someten a los juzgados y tribunales de [TU_PROVINCIA/CIUDAD].</p>'],
            'en' => ['content' => '<h2>1. Identification of Responsible Party</h2><p>In compliance with the Information Society Services Law (LSSI-CE) and the General Data Protection Regulation (GDPR 2016/679), the following identification information is made available to the user:</p><ul><li><strong>Corporate Name:</strong> [TU_NOMBRE_EMPRESA]</li><li><strong>Address:</strong> Calle Mayor 123, 28001 Madrid, Spain</li><li><strong>Tax Identification Number (NIF):</strong> [TU_NIF_CIF_REAL]</li><li><strong>Merchant Registry:</strong> Madrid, Sheet M-000000, Entry 1</li><li><strong>Legal Representative:</strong> [Name and position of administrator]</li><li><strong>Contact Email:</strong> [TU_EMAIL_LEGAL]</li></ul><h2>2. Data Processing Responsible Party</h2><p>The party responsible for processing personal data on this website is <strong>[TU_NOMBRE_EMPRESA]</strong> (the "Company"), acting as the data controller as defined in Article 4.7 of GDPR.</p><h2>3. User Rights and Obligations</h2><p>Access and use of this website grants the user the status of USER, who fully accepts all provisions contained in this Legal Notice and other applicable policies. The user commits to using the site in accordance with the law and not infringing third-party rights.</p><h2>4. Intellectual Property Rights</h2><p>All contents, designs, texts, images, trademarks, logos, icons, buttons, software, source code, and other elements of the site are property of [TU_NOMBRE_EMPRESA] or its licensors. They are protected by intellectual and industrial property laws. Any unauthorized use, reproduction, distribution, public communication, or transformation of these contents without prior written consent is expressly prohibited.</p><h2>5. Disclaimer of Warranties</h2><p>[TU_NOMBRE_EMPRESA] makes no warranties regarding the continuity, availability, accuracy, or correctness of the website. User relies on the site entirely at their own risk. The company is not responsible for:</p><ul><li>Interruptions or lapses in service availability</li><li>Technical errors or defects in published information</li><li>Damages arising from access, use, or inability to access contents</li><li>Viruses, malware, or cyberattacks suffered</li><li>Third-party links or external content available on this site</li></ul><h2>6. Limitation of Liability</h2><p>In no event shall [TU_NOMBRE_EMPRESA] be liable for direct, indirect, incidental, special, exemplary, or consequential damages arising from the use or inability to use the site, even if notified of the possibility of such damages.</p><h2>7. Applicable Law and Jurisdiction</h2><p>This Legal Notice is governed by Spanish law, specifically by Law 34/1988 of November 11 on the Regulation of Advertising in the Public Sector and LSSI-CE. Any disputes shall be subject to the courts and tribunals of Madrid.</p>']
        ], $langIds);


        // ── 2. Política de Privacidad (privacy-policy) ─────────────────────────
        $privacyId = $this->upsertLegalPage('politica-privacidad', 'privacy', [
            'status'             => 'published',
            'published_at'       => $now,
            'scheduled_at'       => null,
            'sort_order'         => 110,
            'sitemap_priority'   => '0.5',
            'sitemap_changefreq' => 'monthly',
            'is_in_sitemap'      => 1,
        ]);
        $this->upsertPageTranslation($privacyId, $langIds['es'], [
            'slug'             => 'politica-privacidad',
            'title'            => 'Política de Privacidad',
            'excerpt'          => 'Información detallada sobre el tratamiento de sus datos personales.',
            'meta_title'       => 'Política de Privacidad | Mi Sitio',
            'meta_description' => 'Descubre cómo recopilamos, usamos y protegemos tus datos de carácter personal.',
            'canonical_url'    => null,
            'robots'           => 'index, follow',
            'schema_data'      => null,
        ]);
        $this->upsertPageTranslation($privacyId, $langIds['en'], [
            'slug'             => 'privacy-policy',
            'title'            => 'Privacy Policy',
            'excerpt'          => 'Detailed information on how we handle your personal data.',
            'meta_title'       => 'Privacy Policy | My Site',
            'meta_description' => 'Learn how we collect, use, and protect your personal data.',
            'canonical_url'    => null,
            'robots'           => 'index, follow',
            'schema_data'      => null,
        ]);

        $this->resetPageBlocks($privacyId);
        $this->upsertBlock($privacyId, $blockIds, 'page_header', 1, [], [
            'es' => ['heading' => 'Política de Privacidad', 'subheading' => 'Protección de datos conforme al GDPR y legislación española vigente.'],
            'en' => ['heading' => 'Privacy Policy', 'subheading' => 'Data protection in compliance with GDPR and current Spanish legislation.']
        ], $langIds);
        $this->upsertBlock($privacyId, $blockIds, 'anchor_nav', 2, [], [
            'es' => [
                'anchors' => [
                    ['label' => '1. Responsable del Tratamiento', 'anchor_id' => 'responsable'],
                    ['label' => '2. Datos Personales Recopilados', 'anchor_id' => 'datos-colectados'],
                    ['label' => '3. Base Legal del Tratamiento', 'anchor_id' => 'base-legal'],
                    ['label' => '4. Finalidades del Tratamiento', 'anchor_id' => 'finalidades'],
                    ['label' => '5. Destinatarios', 'anchor_id' => 'destinatarios'],
                    ['label' => '6. Plazo de Conservación', 'anchor_id' => 'conservacion'],
                    ['label' => '7. Derechos ARCO/GDPR', 'anchor_id' => 'derechos'],
                    ['label' => '8. Seguridad de Datos', 'anchor_id' => 'seguridad'],
                    ['label' => '9. Cambios en esta Política', 'anchor_id' => 'cambios']
                ]
            ],
            'en' => [
                'anchors' => [
                    ['label' => '1. Data Controller', 'anchor_id' => 'controller'],
                    ['label' => '2. Personal Data Collected', 'anchor_id' => 'data-collected'],
                    ['label' => '3. Legal Basis for Processing', 'anchor_id' => 'legal-basis'],
                    ['label' => '4. Processing Purposes', 'anchor_id' => 'purposes'],
                    ['label' => '5. Recipients', 'anchor_id' => 'recipients'],
                    ['label' => '6. Retention Period', 'anchor_id' => 'retention'],
                    ['label' => '7. Your Rights', 'anchor_id' => 'your-rights'],
                    ['label' => '8. Data Security', 'anchor_id' => 'security'],
                    ['label' => '9. Policy Updates', 'anchor_id' => 'updates']
                ]
            ]
        ], $langIds);
        $this->upsertBlock($privacyId, $blockIds, 'rich_text', 3, [], [
            'es' => ['content' => '<div id="responsable" class="scroll-mt-20"><h2>1. Responsable del Tratamiento</h2><p>El responsable del tratamiento de sus datos personales es:</p><ul><li><strong>[TU_NOMBRE_EMPRESA]</strong></li><li>[TU_DIRECCIÓN_COMPLETA]</li><li>NIF: [TU_NIF_CIF_REAL]</li><li>Correo: [TU_EMAIL_PRIVACIDAD]</li></ul><p>Para cualquier consulta sobre el tratamiento de tus datos, puedes contactar con nuestro Delegado de Protección de Datos en <strong>[TU_EMAIL_DPO]</strong>.</p></div><div id="datos-colectados" class="scroll-mt-20"><h2>2. Datos Personales Recopilados</h2><p>Recopilamos los siguientes datos personales cuando interactúas con nuestro sitio:</p><ul><li><strong>Datos de Contacto:</strong> Nombre, apellidos, correo electrónico, número de teléfono</li><li><strong>Datos de Formulario:</strong> Información que proporcionas voluntariamente a través de formularios de contacto</li><li><strong>Datos de Navegación:</strong> Dirección IP, tipo de navegador, páginas visitadas, hora y duración de las visitas</li><li><strong>Datos de Cookies:</strong> Identificadores de sesión y preferencias de usuario (ver Política de Cookies)</li><li><strong>Datos de Ubicación:</strong> En su caso, con previo consentimiento</li></ul></div><div id="base-legal" class="scroll-mt-20"><h2>3. Base Legal del Tratamiento</h2><p>El tratamiento de tus datos personales se realiza con base en:</p><ul><li><strong>Tu consentimiento expreso</strong> (Art. 6.1.a GDPR) - para contacto, boletín, y marketing</li><li><strong>Cumplimiento de obligaciones legales</strong> (Art. 6.1.c GDPR) - retención fiscal, contable y legal</li><li><strong>Intereses legítimos</strong> (Art. 6.1.f GDPR) - mejora del servicio, análisis de seguridad</li><li><strong>Obligación legal</strong> según LSSI-CE para identificación del responsable</li></ul></div><div id="finalidades" class="scroll-mt-20"><h2>4. Finalidades del Tratamiento</h2><p>Tratamos tus datos para:</p><ul><li>Responder a consultas y solicitudes de información</li><li>Gestionar tu suscripción a nuestro boletín electrónico</li><li>Enviar información sobre productos/servicios bajo consentimiento</li><li>Analizar el uso del sitio para mejorar la experiencia del usuario</li><li>Cumplir obligaciones legales y fiscales</li><li>Prevenir fraude y garantizar la seguridad del sitio</li><li>Comunicarnos contigo cuando lo considere necesario</li></ul></div><div id="destinatarios" class="scroll-mt-20"><h2>5. Destinatarios de los Datos</h2><p>Tus datos pueden ser compartidos con:</p><ul><li><strong>Proveedores de Servicios:</strong> Alojamiento web, correo electrónico, analítica (Google Analytics)</li><li><strong>Autoridades Públicas:</strong> Cuando la ley lo requiera</li><li><strong>Socios Comerciales:</strong> Con tu consentimiento previo</li></ul><p>No realizamos transferencias internacionales de datos fuera de la UE sin garantías adecuadas.</p></div><div id="conservacion" class="scroll-mt-20"><h2>6. Plazo de Conservación</h2><p>Conservaremos tus datos personales durante el tiempo necesario para cumplir las finalidades del tratamiento:</p><ul><li><strong>Datos de Contacto:</strong> Mientras mantengas relación con nosotros + 3 años tras su conclusión</li><li><strong>Datos de Marketing:</strong> Hasta revocar tu consentimiento</li><li><strong>Datos Fiscales/Contables:</strong> 6 años según obligaciones legales</li><li><strong>Datos de Navegación:</strong> Máximo 13 meses para Google Analytics</li></ul></div><div id="derechos" class="scroll-mt-20"><h2>7. Tus Derechos ARCO/GDPR</h2><p>Tienes derecho a:</p><ul><li><strong>Derecho de Acceso (Art. 15 GDPR):</strong> Obtener confirmación de si tratamos tus datos</li><li><strong>Derecho de Rectificación (Art. 16 GDPR):</strong> Corregir datos inexactos o incompletos</li><li><strong>Derecho de Supresión (Art. 17 GDPR):</strong> Eliminar tus datos (derecho al olvido)</li><li><strong>Derecho de Limitación (Art. 18 GDPR):</strong> Limitar el procesamiento de tus datos</li><li><strong>Derecho a la Portabilidad (Art. 20 GDPR):</strong> Recibir tus datos en formato estructurado</li><li><strong>Derecho de Oposición (Art. 21 GDPR):</strong> Oponerté al tratamiento de datos</li></ul><p>Para ejercer cualquiera de estos derechos, contacta con [TU_EMAIL_PRIVACIDAD]. Responderemos en el plazo legal de 30 días.</p></div><div id="seguridad" class="scroll-mt-20"><h2>8. Seguridad de Datos</h2><p>Implementamos medidas técnicas y organizativas para proteger tus datos contra acceso no autorizado, pérdida, alteración o divulgación:</p><ul><li>Encriptación de datos en tránsito (HTTPS/TLS)</li><li>Controles de acceso y autenticación</li><li>Auditorías de seguridad regulares</li><li>Cumplimiento de estándares ISO 27001</li><li>Plan de respuesta a incidentes de seguridad</li></ul></div><div id="cambios" class="scroll-mt-20"><h2>9. Cambios en esta Política</h2><p>Nos reservamos el derecho de actualizar esta Política de Privacidad en cualquier momento. Los cambios significativos serán comunicados mediante correo electrónico o aviso destacado en el sitio. Tu uso continuado del sitio implica aceptación de cualquier cambio.</p><p><strong>Última actualización:</strong> 14 de julio de 2026</p></div>'],
            'en' => ['content' => '<div id="controller" class="scroll-mt-20"><h2>1. Data Controller</h2><p>The controller responsible for processing your personal data is:</p><ul><li><strong>[TU_NOMBRE_EMPRESA]</strong></li><li>Calle Mayor 123, 28001 Madrid, Spain</li><li>Tax ID: [TU_NIF_CIF_REAL]</li><li>Email: privacy@example.com</li></ul><p>For any questions about your data processing, you can contact our Data Protection Officer at <strong>dpo@example.com</strong>.</p></div><div id="data-collected" class="scroll-mt-20"><h2>2. Personal Data Collected</h2><p>We collect the following personal data when you interact with our website:</p><ul><li><strong>Contact Data:</strong> Name, surname, email address, phone number</li><li><strong>Form Data:</strong> Information you voluntarily provide through contact forms</li><li><strong>Navigation Data:</strong> IP address, browser type, pages visited, visit time and duration</li><li><strong>Cookie Data:</strong> Session identifiers and user preferences (see Cookie Policy)</li><li><strong>Location Data:</strong> Where applicable, with prior consent</li></ul></div><div id="legal-basis" class="scroll-mt-20"><h2>3. Legal Basis for Processing</h2><p>We process your personal data based on:</p><ul><li><strong>Your Express Consent</strong> (Art. 6.1.a GDPR) - for contact, newsletter, marketing</li><li><strong>Legal Obligation</strong> (Art. 6.1.c GDPR) - tax, accounting, and legal retention</li><li><strong>Legitimate Interests</strong> (Art. 6.1.f GDPR) - service improvement, security analysis</li><li><strong>Legal Requirement</strong> per LSSI-CE for responsible party identification</li></ul></div><div id="purposes" class="scroll-mt-20"><h2>4. Processing Purposes</h2><p>We process your data for:</p><ul><li>Responding to inquiries and information requests</li><li>Managing your newsletter subscription</li><li>Sending information about products/services with your consent</li><li>Analyzing website usage to improve user experience</li><li>Complying with legal and tax obligations</li><li>Preventing fraud and ensuring website security</li><li>Communicating with you when necessary</li></ul></div><div id="recipients" class="scroll-mt-20"><h2>5. Data Recipients</h2><p>Your data may be shared with:</p><ul><li><strong>Service Providers:</strong> Web hosting, email, analytics (Google Analytics)</li><li><strong>Public Authorities:</strong> When required by law</li><li><strong>Business Partners:</strong> With your prior consent</li></ul><p>We do not transfer data outside the EU without adequate safeguards.</p></div><div id="retention" class="scroll-mt-20"><h2>6. Retention Period</h2><p>We retain your personal data as long as necessary to fulfill processing purposes:</p><ul><li><strong>Contact Data:</strong> While you maintain relationship with us + 3 years after</li><li><strong>Marketing Data:</strong> Until you withdraw consent</li><li><strong>Tax/Accounting Data:</strong> 6 years per legal requirements</li><li><strong>Navigation Data:</strong> Maximum 13 months for Google Analytics</li></ul></div><div id="your-rights" class="scroll-mt-20"><h2>7. Your GDPR Rights</h2><p>You have the right to:</p><ul><li><strong>Right to Access (Art. 15 GDPR):</strong> Obtain confirmation of data processing</li><li><strong>Right to Rectification (Art. 16 GDPR):</strong> Correct inaccurate or incomplete data</li><li><strong>Right to Erasure (Art. 17 GDPR):</strong> Delete your data (right to be forgotten)</li><li><strong>Right to Restrict (Art. 18 GDPR):</strong> Restrict data processing</li><li><strong>Right to Portability (Art. 20 GDPR):</strong> Receive your data in structured format</li><li><strong>Right to Object (Art. 21 GDPR):</strong> Object to data processing</li></ul><p>To exercise any of these rights, contact privacy@example.com. We will respond within the legal timeframe of 30 days.</p></div><div id="security" class="scroll-mt-20"><h2>8. Data Security</h2><p>We implement technical and organizational measures to protect your data from unauthorized access, loss, alteration, or disclosure:</p><ul><li>Encryption of data in transit (HTTPS/TLS)</li><li>Access controls and authentication</li><li>Regular security audits</li><li>ISO 27001 standards compliance</li><li>Security incident response plan</li></ul></div><div id="updates" class="scroll-mt-20"><h2>9. Policy Updates</h2><p>We reserve the right to update this Privacy Policy at any time. Significant changes will be communicated via email or prominent notice on the site. Your continued use of the site implies acceptance of any changes.</p><p><strong>Last Updated:</strong> July 14, 2026</p></div>']
        ], $langIds);


        // ── 3. Política de Cookies (cookie-policy) ───────────────────────────
        $cookiesId = $this->upsertLegalPage('politica-cookies', 'generic', [
            'status'             => 'published',
            'published_at'       => $now,
            'scheduled_at'       => null,
            'sort_order'         => 120,
            'sitemap_priority'   => '0.3',
            'sitemap_changefreq' => 'monthly',
            'is_in_sitemap'      => 1,
        ]);
        $this->upsertPageTranslation($cookiesId, $langIds['es'], [
            'slug'             => 'politica-cookies',
            'title'            => 'Política de Cookies',
            'excerpt'          => 'Detalles sobre las cookies y tecnologías similares utilizadas en nuestra web.',
            'meta_title'       => 'Política de Cookies | Mi Sitio',
            'meta_description' => 'Conozca qué cookies utilizamos y cómo administrarlas.',
            'canonical_url'    => null,
            'robots'           => 'index, follow',
            'schema_data'      => null,
        ]);
        $this->upsertPageTranslation($cookiesId, $langIds['en'], [
            'slug'             => 'cookie-policy',
            'title'            => 'Cookie Policy',
            'excerpt'          => 'Information about the cookies and tracking technologies we use.',
            'meta_title'       => 'Cookie Policy | My Site',
            'meta_description' => 'Understand what cookies we set and how to manage them.',
            'canonical_url'    => null,
            'robots'           => 'index, follow',
            'schema_data'      => null,
        ]);

        $this->resetPageBlocks($cookiesId);
        $this->upsertBlock($cookiesId, $blockIds, 'page_header', 1, [], [
            'es' => ['heading' => 'Política de Cookies', 'subheading' => 'Información detallada sobre el uso de cookies conforme a LSSI-CE y GDPR.'],
            'en' => ['heading' => 'Cookie Policy', 'subheading' => 'Detailed information about cookie usage in compliance with LSSI-CE and GDPR.']
        ], $langIds);
        $this->upsertBlock($cookiesId, $blockIds, 'rich_text', 2, [], [
            'es' => ['content' => '<h2>¿Qué son las cookies?</h2><p>Una cookie es un pequeño fichero de texto que se almacena en tu navegador al visitar nuestro sitio web. Las cookies permiten que el sitio recuerde información sobre ti durante tus visitas, mejorando tu experiencia de usuario. Este sitio puede instalar cookies cuando navegas por sus páginas.</p><h2>Tipos de Cookies según su Duración</h2><ul><li><strong>Cookies de Sesión:</strong> Se eliminan automáticamente al cerrar el navegador</li><li><strong>Cookies Persistentes:</strong> Permanecen en tu dispositivo durante un período especificado (días, meses o años)</li></ul><h2>Tipos de Cookies según su Origen</h2><ul><li><strong>Cookies Propias:</strong> Generadas y gestionadas por [TU_NOMBRE_EMPRESA]</li><li><strong>Cookies de Terceros:</strong> Generadas por terceros (Google Analytics, CDN, redes sociales, etc.)</li></ul><h2>Tipos de Cookies según su Funcionalidad</h2><ul><li><strong>Cookies Técnicas/Necesarias:</strong> Esenciales para el funcionamiento del sitio (seguridad, autenticación)</li><li><strong>Cookies de Preferencias:</strong> Almacenan preferencias del usuario (idioma, tema visual)</li><li><strong>Cookies Analíticas:</strong> Recopilan información sobre cómo se utiliza el sitio (Google Analytics)</li><li><strong>Cookies de Marketing:</strong> Utilizadas para campaña publicitaria y publicidad personalizada</li></ul><h2>Cookies Utilizadas en este Sitio Web</h2><table class="w-full border-collapse border border-slate-300 mt-4 text-sm text-left"><thead><tr class="bg-slate-100"><th class="border border-slate-300 p-3 font-semibold">Nombre</th><th class="border border-slate-300 p-3 font-semibold">Tipo</th><th class="border border-slate-300 p-3 font-semibold">Duración</th><th class="border border-slate-300 p-3 font-semibold">Finalidad</th><th class="border border-slate-300 p-3 font-semibold">Consentimiento</th></tr></thead><tbody><tr><td class="border border-slate-300 p-3">ci_session</td><td class="border border-slate-300 p-3">Técnica</td><td class="border border-slate-300 p-3">Sesión</td><td class="border border-slate-300 p-3">Mantener la sesión del usuario en el sitio</td><td class="border border-slate-300 p-3">No requerido*</td></tr><tr><td class="border border-slate-300 p-3">cookie_consent</td><td class="border border-slate-300 p-3">Preferencia</td><td class="border border-slate-300 p-3">1 año</td><td class="border border-slate-300 p-3">Almacenar tus preferencias de consentimiento de cookies</td><td class="border border-slate-300 p-3">Implícito</td></tr><tr><td class="border border-slate-300 p-3">_ga</td><td class="border border-slate-300 p-3">Analítica</td><td class="border border-slate-300 p-3">2 años</td><td class="border border-slate-300 p-3">Identificar usuarios únicos (Google Analytics)</td><td class="border border-slate-300 p-3">Requerido</td></tr><tr><td class="border border-slate-300 p-3">_ga_[container-id]</td><td class="border border-slate-300 p-3">Analítica</td><td class="border border-slate-300 p-3">2 años</td><td class="border border-slate-300 p-3">Registrar sesiones de Google Analytics</td><td class="border border-slate-300 p-3">Requerido</td></tr><tr><td class="border border-slate-300 p-3">NID (si aplica)</td><td class="border border-slate-300 p-3">Marketing</td><td class="border border-slate-300 p-3">6 meses</td><td class="border border-slate-300 p-3">Publicidad personalizada (Google)</td><td class="border border-slate-300 p-3">Requerido</td></tr><tr><td class="border border-slate-300 p-3">PHPSESSID</td><td class="border border-slate-300 p-3">Técnica</td><td class="border border-slate-300 p-3">Sesión</td><td class="border border-slate-300 p-3">Mantener datos de sesión PHP en el servidor</td><td class="border border-slate-300 p-3">No requerido*</td></tr></tbody></table><p><small>* Las cookies técnicas necesarias para el funcionamiento del sitio no requieren consentimiento previo según el artículo 22.2 LSSI-CE.</small></p><h2>Consentimiento y Control de Cookies</h2><p>Al acceder a este sitio, recibirás una notificación sobre cookies. Puedes:</p><ul><li><strong>Aceptar todas las cookies:</strong> Aceptas el uso de todas las categorías de cookies</li><li><strong>Gestionar preferencias:</strong> Elegir qué tipos de cookies permitir</li><li><strong>Rechazar cookies no esenciales:</strong> Solo se instalarán cookies técnicas necesarias</li></ul><p>Puedes cambiar tus preferencias en cualquier momento accediendo a la configuración de cookies del navegador o contactándonos.</p><h2>Gestión de Cookies en tu Navegador</h2><p>Puedes controlar y eliminar cookies directamente desde tu navegador:</p><ul><li><strong>Chrome:</strong> Menú → Configuración → Privacidad y seguridad → Cookies</li><li><strong>Firefox:</strong> Menú → Preferencias → Privacidad y seguridad → Cookies</li><li><strong>Safari:</strong> Preferencias → Privacidad → Gestionar datos del sitio web</li><li><strong>Edge:</strong> Configuración → Privacidad y servicios → Borrar datos de exploración</li></ul><p>Ten en cuenta que desactivar cookies puede afectar la funcionalidad del sitio.</p><h2>Cookies de Terceros y Enlaces Externos</h2><p>Este sitio puede contener enlaces a sitios web de terceros. Esta Política de Cookies no se aplica a terceros. Te recomendamos consultar sus políticas de cookies respectivas.</p><h2>Cambios en esta Política</h2><p>Nos reservamos el derecho de actualizar esta Política de Cookies en cualquier momento. Cualquier cambio significativo será notificado mediante aviso en el sitio.</p><p><strong>Última actualización:</strong> 14 de julio de 2026</p>'],
            'en' => ['content' => '<h2>What are Cookies?</h2><p>A cookie is a small text file stored in your browser when visiting our website. Cookies allow the site to remember information about you during visits, improving your user experience. This site may install cookies when you browse its pages.</p><h2>Types of Cookies by Duration</h2><ul><li><strong>Session Cookies:</strong> Automatically deleted when closing the browser</li><li><strong>Persistent Cookies:</strong> Remain on your device for a specified period (days, months, or years)</li></ul><h2>Types of Cookies by Origin</h2><ul><li><strong>First-Party Cookies:</strong> Generated and managed by [TU_NOMBRE_EMPRESA]</li><li><strong>Third-Party Cookies:</strong> Generated by third parties (Google Analytics, CDN, social networks, etc.)</li></ul><h2>Types of Cookies by Functionality</h2><ul><li><strong>Technical/Necessary Cookies:</strong> Essential for site functioning (security, authentication)</li><li><strong>Preference Cookies:</strong> Store user preferences (language, theme)</li><li><strong>Analytical Cookies:</strong> Collect information about site usage (Google Analytics)</li><li><strong>Marketing Cookies:</strong> Used for advertising campaigns and personalized ads</li></ul><h2>Cookies Used on this Website</h2><table class="w-full border-collapse border border-slate-300 mt-4 text-sm text-left"><thead><tr class="bg-slate-100"><th class="border border-slate-300 p-3 font-semibold">Name</th><th class="border border-slate-300 p-3 font-semibold">Type</th><th class="border border-slate-300 p-3 font-semibold">Duration</th><th class="border border-slate-300 p-3 font-semibold">Purpose</th><th class="border border-slate-300 p-3 font-semibold">Consent Required</th></tr></thead><tbody><tr><td class="border border-slate-300 p-3">ci_session</td><td class="border border-slate-300 p-3">Technical</td><td class="border border-slate-300 p-3">Session</td><td class="border border-slate-300 p-3">Maintain user session on the site</td><td class="border border-slate-300 p-3">Not required*</td></tr><tr><td class="border border-slate-300 p-3">cookie_consent</td><td class="border border-slate-300 p-3">Preference</td><td class="border border-slate-300 p-3">1 year</td><td class="border border-slate-300 p-3">Store your cookie consent preferences</td><td class="border border-slate-300 p-3">Implicit</td></tr><tr><td class="border border-slate-300 p-3">_ga</td><td class="border border-slate-300 p-3">Analytics</td><td class="border border-slate-300 p-3">2 years</td><td class="border border-slate-300 p-3">Identify unique users (Google Analytics)</td><td class="border border-slate-300 p-3">Required</td></tr><tr><td class="border border-slate-300 p-3">_ga_[container-id]</td><td class="border border-slate-300 p-3">Analytics</td><td class="border border-slate-300 p-3">2 years</td><td class="border border-slate-300 p-3">Record Google Analytics sessions</td><td class="border border-slate-300 p-3">Required</td></tr><tr><td class="border border-slate-300 p-3">NID (if applicable)</td><td class="border border-slate-300 p-3">Marketing</td><td class="border border-slate-300 p-3">6 months</td><td class="border border-slate-300 p-3">Personalized advertising (Google)</td><td class="border border-slate-300 p-3">Required</td></tr><tr><td class="border border-slate-300 p-3">PHPSESSID</td><td class="border border-slate-300 p-3">Technical</td><td class="border border-slate-300 p-3">Session</td><td class="border border-slate-300 p-3">Maintain PHP session data on server</td><td class="border border-slate-300 p-3">Not required*</td></tr></tbody></table><p><small>* Technical cookies necessary for site functioning do not require prior consent per Article 22.2 LSSI-CE.</small></p><h2>Consent and Cookie Control</h2><p>When accessing this site, you will receive a cookie notification. You can:</p><ul><li><strong>Accept all cookies:</strong> Accept all cookie categories</li><li><strong>Manage preferences:</strong> Choose which cookie types to allow</li><li><strong>Reject non-essential cookies:</strong> Only necessary technical cookies will be installed</li></ul><p>You can change your preferences at any time by accessing your browser\'s cookie settings or contacting us.</p><h2>Browser Cookie Management</h2><p>You can control and delete cookies directly from your browser:</p><ul><li><strong>Chrome:</strong> Menu → Settings → Privacy and Security → Cookies</li><li><strong>Firefox:</strong> Menu → Preferences → Privacy & Security → Cookies</li><li><strong>Safari:</strong> Preferences → Privacy → Manage Website Data</li><li><strong>Edge:</strong> Settings → Privacy → Clear browsing data</li></ul><p>Note that disabling cookies may affect site functionality.</p><h2>Third-Party Cookies and External Links</h2><p>This site may contain links to third-party websites. This Cookie Policy does not apply to third parties. We recommend reviewing their respective cookie policies.</p><h2>Policy Updates</h2><p>We reserve the right to update this Cookie Policy at any time. Any significant changes will be notified via notice on the site.</p><p><strong>Last Updated:</strong> July 14, 2026</p>']
        ], $langIds);


        // ── 4. Derechos de Datos (ARCO) (data-rights) ────────────────────────
        $dataRightsId = $this->upsertLegalPage('derechos-datos', 'generic', [
            'status'             => 'published',
            'published_at'       => $now,
            'scheduled_at'       => null,
            'sort_order'         => 130,
            'sitemap_priority'   => '0.4',
            'sitemap_changefreq' => 'monthly',
            'is_in_sitemap'      => 1,
        ]);
        $this->upsertPageTranslation($dataRightsId, $langIds['es'], [
            'slug'             => 'derechos-datos',
            'title'            => 'Derechos de Datos',
            'excerpt'          => 'Formulario y preguntas frecuentes para ejercer sus derechos ARCO/RGPD.',
            'meta_title'       => 'Derechos de Datos | Mi Sitio',
            'meta_description' => 'Ejercite sus derechos de Acceso, Rectificación, Supresión u Oposición sobre sus datos.',
            'canonical_url'    => null,
            'robots'           => 'index, follow',
            'schema_data'      => null,
        ]);
        $this->upsertPageTranslation($dataRightsId, $langIds['en'], [
            'slug'             => 'data-rights',
            'title'            => 'Data Rights',
            'excerpt'          => 'Form and FAQs to exercise your GDPR rights over your personal data.',
            'meta_title'       => 'Data Subject Rights | My Site',
            'meta_description' => 'Exercise your rights of Access, Rectification, Erasure, or Objection.',
            'canonical_url'    => null,
            'robots'           => 'index, follow',
            'schema_data'      => null,
        ]);

        $this->resetPageBlocks($dataRightsId);
        $this->upsertBlock($dataRightsId, $blockIds, 'page_header', 1, [], [
            'es' => ['heading' => 'Control sobre sus Datos', 'subheading' => 'Canal oficial para ejercer sus derechos ARCO / RGPD.'],
            'en' => ['heading' => 'Control Your Data', 'subheading' => 'Official channel to exercise your GDPR rights.']
        ], $langIds);
        $this->upsertBlock($dataRightsId, $blockIds, 'faq_accordion', 2, [], [
            'es' => [
                'title' => 'Preguntas Frecuentes - Derechos de Datos',
                'description' => 'Respuestas detalladas sobre tus derechos ARCO y GDPR.',
                'faqs' => [
                    ['question' => '¿Qué son los derechos ARCO?', 'answer' => '<p><strong>ARCO</strong> es un acrónimo que significa:</p><ul><li><strong>Acceso:</strong> Derecho a obtener información de si tus datos están siendo procesados</li><li><strong>Rectificación:</strong> Derecho a corregir datos inexactos o incompletos</li><li><strong>Cancelación:</strong> Derecho a solicitar la eliminación de tus datos (derecho al olvido)</li><li><strong>Oposición:</strong> Derecho a oponerté al procesamiento de tus datos</li></ul><p>Estos derechos están regulados por la Ley Orgánica de Protección de Datos (LOPD) española y reforzados por el GDPR europeo.</p>'],
                    ['question' => '¿Cuánto tiempo tarda en tramitarse mi solicitud?', 'answer' => '<p>De acuerdo con el Artículo 12 del GDPR y la legislación española vigente, procesaremos y responderemos a tu solicitud en un plazo máximo de <strong>30 días hábiles</strong> desde su recepción. Si la solicitud es compleja, podemos extender este plazo otros 60 días, notificándote de la extensión.</p>'],
                    ['question' => '¿Es gratuito ejercer estos derechos?', 'answer' => '<p>Sí, el ejercicio de los derechos ARCO es <strong>completamente gratuito</strong> según el GDPR. No podemos cobrar tasas o honorarios por procesar tus solicitudes, a excepción de casos excepcionales de solicitudes manifiestamente infundadas o excesivas (Art. 12.5 GDPR).</p>'],
                    ['question' => '¿Qué información debo proporcionar en mi solicitud?', 'answer' => '<p>Para procesar tu solicitud, necesitaremos:</p><ul><li>Tu nombre completo y datos de contacto</li><li>Email o dirección postal para responder</li><li>Descripción clara del derecho que deseas ejercer</li><li>Datos específicos que ayuden a identificarte (ej: email con el que te registraste)</li><li>Fotocopia de tu DNI, pasaporte u otro documento de identidad</li></ul><p>Verificamos la identidad para garantizar que solo tú puedas acceder a tus datos.</p>'],
                    ['question' => '¿Cómo presento una solicitud de derecho de acceso?', 'answer' => '<p>Puedes ejercer tu derecho de acceso:</p><ul><li>Completando el formulario en esta página</li><li>Enviando un email a [TU_EMAIL_PRIVACIDAD] con el asunto "Solicitud de Acceso GDPR"</li><li>Enviando una carta certificada a: [TU_NOMBRE_EMPRESA], Calle Mayor 123, 28001 Madrid</li></ul><p>En tu solicitud, especifica claramente que solicitas acceso a todos tus datos personales y en qué formato prefieres recibirlos.</p>'],
                    ['question' => '¿Puedo solicitar el derecho al olvido? ¿Se eliminarán todos mis datos?', 'answer' => '<p>Sí, tienes derecho a solicitar la eliminación de tus datos (Art. 17 GDPR - Derecho al Olvido). Sin embargo, <strong>no siempre es posible eliminarlos todos</strong> por razones legales:</p><ul><li><strong>Obligaciones legales:</strong> Algunos datos se conservan por mandato legal (fiscal, contable, etc.) durante períodos fijos</li><li><strong>Défensa de derechos:</strong> Podemos retener datos para defendernos ante demandas</li><li><strong>Datos públicos:</strong> Aunque eliminemos nuestros datos, pueden estar indexados en buscadores</li></ul><p>Evaluaremos tu solicitud y te informaremos qué datos pueden eliminarse y cuáles deben conservarse por ley.</p>'],
                    ['question' => '¿Qué es la portabilidad de datos? ¿Cómo solicito mis datos en otro formato?', 'answer' => '<p>La portabilidad de datos (Art. 20 GDPR) es tu derecho a recibir tus datos personales en un formato estructurado, comúnmente utilizado y legible por máquina (ej: CSV, JSON, XML).</p><p>Esto te permite:</p><ul><li>Transferir fácilmente tus datos entre proveedores</li><li>Tener control total sobre tu información</li><li>Cambiar de servicio sin perder tus datos</li></ul><p>Solicita este derecho indicando qué formato prefieres en el formulario.</p>'],
                    ['question' => '¿Puedo limitación del tratamiento de mis datos?', 'answer' => '<p>Sí (Art. 18 GDPR). La limitación del tratamiento te permite "congelar" el procesamiento de tus datos mientras se resuelve una disputa. Durante este período:</p><ul><li>Conservaremos tus datos pero no los procesaremos</li><li>Te informaremos antes de levantar la limitación</li><li>Solo continuaremos si tú das consentimiento o por obligación legal</li></ul><p>Es útil si impugnas la exactitud de datos o la legitimidad del procesamiento.</p>'],
                    ['question' => '¿Puedo oponerme al envío de marketing?', 'answer' => '<p>Sí, tienes derecho de oposición (Art. 21 GDPR) para dejar de recibir marketing. Puedes:</p><ul><li>Hacer clic en "Desuscribirse" en cualquier email de marketing</li><li>Contactarnos en [TU_EMAIL_PRIVACIDAD] solicitando baja de marketing</li><li>Usar el formulario en esta página indicando "Derecho de Oposición"</li></ul><p>Procesa tu solicitud en un plazo máximo de 10 días hábiles.</p>'],
                    ['question' => '¿Qué pasa si no estoy satisfecho con la respuesta?', 'answer' => '<p>Si consideras que tu solicitud no ha sido resuelta adecuadamente, tienes opciones:</p><ul><li><strong>Contactar nuevamente:</strong> Envía una queja formal a [TU_EMAIL_PRIVACIDAD] explicando tu insatisfacción</li><li><strong>Presentar reclamación ante la Autoridad de Protección de Datos:</strong> En España, contacta con la AEPD (Agencia Española de Protección de Datos) en www.aepd.es o teléfono 900 665 044</li><li><strong>Acción legal:</strong> Tienes derecho a acudir a los tribunales (Art. 82 GDPR)</li></ul><p>Tu reclamación ante la AEPD es completamente gratuita.</p>']
                ]
            ],
            'en' => [
                'title' => 'FAQs - Data Subject Rights',
                'description' => 'Detailed answers about your GDPR rights.',
                'faqs' => [
                    ['question' => 'What are GDPR rights?', 'answer' => '<p><strong>GDPR rights</strong> allow you to control your personal data:</p><ul><li><strong>Right to Access:</strong> Know if your data is being processed</li><li><strong>Right to Rectification:</strong> Correct inaccurate or incomplete data</li><li><strong>Right to Erasure:</strong> Request deletion of your data (right to be forgotten)</li><li><strong>Right to Restrict:</strong> Limit how your data is processed</li><li><strong>Right to Portability:</strong> Receive your data in portable format</li><li><strong>Right to Object:</strong> Oppose data processing</li></ul><p>These rights are regulated by EU GDPR and Spanish data protection law.</p>'],
                    ['question' => 'How long does it take to process my request?', 'answer' => '<p>According to GDPR Article 12 and Spanish law, we will process and respond to your request within a maximum of <strong>30 business days</strong> from receipt. For complex requests, we may extend this by 60 additional days, notifying you of the extension.</p>'],
                    ['question' => 'Is it free to exercise these rights?', 'answer' => '<p>Yes, exercising your data rights is <strong>completely free</strong> under GDPR. We cannot charge fees, except in exceptional cases of manifestly unfounded or excessive requests (Art. 12.5 GDPR).</p>'],
                    ['question' => 'What information should I provide in my request?', 'answer' => '<p>To process your request, we will need:</p><ul><li>Your full name and contact details</li><li>Email or postal address for response</li><li>Clear description of the right you wish to exercise</li><li>Specific data helping identify you (e.g., registered email)</li><li>Copy of your ID, passport, or identity document</li></ul><p>We verify identity to ensure only you access your data.</p>'],
                    ['question' => 'How do I submit an access request?', 'answer' => '<p>You can exercise your access right by:</p><ul><li>Completing the form on this page</li><li>Emailing privacy@example.com with subject "GDPR Access Request"</li><li>Sending a certified letter to: [TU_NOMBRE_EMPRESA], Calle Mayor 123, 28001 Madrid</li></ul><p>In your request, clearly state you seek access to all your personal data and specify your preferred format.</p>'],
                    ['question' => 'Can I request erasure? Will all my data be deleted?', 'answer' => '<p>Yes, you can request data erasure (Art. 17 GDPR - Right to be Forgotten). However, <strong>not all data can always be deleted</strong> for legal reasons:</p><ul><li><strong>Legal obligations:</strong> Some data must be retained by law (tax, accounting, etc.)</li><li><strong>Rights defense:</strong> We may retain data to defend legal claims</li><li><strong>Public data:</strong> Even if we delete it, search engines may have cached it</li></ul><p>We\'ll assess your request and inform you which data can be deleted and what must be retained.</p>'],
                    ['question' => 'What is data portability? How do I request my data in another format?', 'answer' => '<p>Data portability (Art. 20 GDPR) is your right to receive your data in a structured, commonly-used, machine-readable format (e.g., CSV, JSON, XML).</p><p>This allows you to:</p><ul><li>Easily transfer data between providers</li><li>Maintain full control of your information</li><li>Switch services without losing your data</li></ul><p>Request this right by indicating your preferred format in the form.</p>'],
                    ['question' => 'Can I restrict processing of my data?', 'answer' => '<p>Yes (Art. 18 GDPR). Restricting processing allows you to "freeze" data handling while disputes are resolved. During this period:</p><ul><li>We retain your data but don\'t process it</li><li>We\'ll notify you before lifting the restriction</li><li>We\'ll only continue with your consent or legal obligation</li></ul><p>Useful if you dispute data accuracy or processing legitimacy.</p>'],
                    ['question' => 'Can I opt out of marketing communications?', 'answer' => '<p>Yes, you have the right to object (Art. 21 GDPR) to stop receiving marketing. You can:</p><ul><li>Click "Unsubscribe" in any marketing email</li><li>Contact us at privacy@example.com requesting opt-out</li><li>Use the form on this page indicating "Right to Object"</li></ul><p>We\'ll process your request within 10 business days maximum.</p>'],
                    ['question' => 'What if I\'m not satisfied with the response?', 'answer' => '<p>If your request isn\'t properly resolved, you have options:</p><ul><li><strong>Contact again:</strong> Send a formal complaint to privacy@example.com explaining your concerns</li><li><strong>File with Data Protection Authority:</strong> Contact your country\'s DPA (Spain: AEPD at www.aepd.es or 900 665 044)</li><li><strong>Legal action:</strong> You can pursue court proceedings (Art. 82 GDPR)</li></ul><p>Filing a complaint with your DPA is completely free.</p>']
                ]
            ]
        ], $langIds);
        $this->upsertBlock($dataRightsId, $blockIds, 'form_embed', 3, ['form_key' => 'gdpr_rights'], [
            'es' => ['heading' => 'Formulario de Solicitud', 'description' => 'Complete el formulario para tramitar de forma inmediata.'],
            'en' => ['heading' => 'Request Form', 'description' => 'Complete the form to initiate your request immediately.']
        ], $langIds);


        // ── 5. Términos de Servicio (terms-of-service) ────────────────────────
        $termsId = $this->upsertLegalPage('terminos-servicio', 'terms', [
            'status'             => 'published',
            'published_at'       => $now,
            'scheduled_at'       => null,
            'sort_order'         => 140,
            'sitemap_priority'   => '0.5',
            'sitemap_changefreq' => 'monthly',
            'is_in_sitemap'      => 1,
        ]);
        $this->upsertPageTranslation($termsId, $langIds['es'], [
            'slug'             => 'terminos-servicio',
            'title'            => 'Términos de Servicio',
            'excerpt'          => 'Reglas y condiciones aplicables al uso de nuestro sitio web.',
            'meta_title'       => 'Términos de Servicio | Mi Sitio',
            'meta_description' => 'Condiciones generales de uso del portal y propiedad intelectual.',
            'canonical_url'    => null,
            'robots'           => 'index, follow',
            'schema_data'      => null,
        ]);
        $this->upsertPageTranslation($termsId, $langIds['en'], [
            'slug'             => 'terms-of-service',
            'title' => 'Terms of Service',
            'excerpt' => 'Rules and terms governing the usage of our website.',
            'meta_title' => 'Terms of Service | My Site',
            'meta_description' => 'General terms of use and intellectual property terms.',
            'canonical_url' => null,
            'robots' => 'index, follow',
            'schema_data' => null,
        ]);

        $this->resetPageBlocks($termsId);
        $this->upsertBlock($termsId, $blockIds, 'page_header', 1, [], [
            'es' => ['heading' => 'Términos de Servicio', 'subheading' => 'Normas y condiciones aplicables a la utilización del sitio web.'],
            'en' => ['heading' => 'Terms of Service', 'subheading' => 'Terms and conditions governing website usage.']
        ], $langIds);
        $this->upsertBlock($termsId, $blockIds, 'rich_text', 2, [], [
            'es' => ['content' => '<h2>1. Objeto y Ámbito de Aplicación</h2><p>Estos Términos de Servicio ("Términos") regulan el acceso y uso del sitio web www.ejemplo.es (el "Sitio") y todos los servicios, contenidos y funcionalidades disponibles en él, operados por [TU_NOMBRE_EMPRESA] (la "Empresa").</p><h2>2. Aceptación de los Términos</h2><p>Al acceder y utilizar este Sitio, aceptas de forma íntegra y sin reservas todos los Términos, Política de Privacidad, Política de Cookies y demás políticas disponibles. Si no estás de acuerdo con alguna disposición, debes dejar de usar el Sitio inmediatamente.</p><h2>3. Usuarios y Capacidad Legal</h2><p>El uso de este Sitio está permitido únicamente a personas con capacidad legal para contratar. Si eres menor de 18 años, necesitas consentimiento expreso de tus padres o tutores. La Empresa se reserva el derecho de verificar la identidad y edad de los usuarios.</p><h2>4. Acceso y Disponibilidad del Sitio</h2><p>La Empresa se esfuerza por mantener el Sitio disponible las 24/7, pero no garantiza:</p><ul><li>Disponibilidad ininterrumpida</li><li>Ausencia de errores técnicos o de seguridad</li><li>Precisión, corrección o actualidad de los contenidos</li><li>Compatibilidad con todos los navegadores o dispositivos</li></ul><p>La Empresa se reserva el derecho de realizar mantenimiento, actualizaciones o suspender el servicio sin previo aviso.</p><h2>5. Propiedad Intelectual e Industrial</h2><p>Todos los contenidos del Sitio, incluyendo pero no limitado a:</p><ul><li>Textos, artículos y descripciones</li><li>Imágenes, fotografías, gráficos y vídeos</li><li>Diseño, layout y estructura del Sitio</li><li>Marcas, logos y signos distintivos</li><li>Código fuente, scripts y aplicaciones web</li><li>Bases de datos y compilaciones de datos</li></ul><p>...son propiedad exclusiva de la Empresa o de sus licenciantes y están protegidos por las leyes de propiedad intelectual e industrial internacionales, incluyendo la Ley de Propiedad Intelectual española (LPI).</p><p>Queda expresamente prohibido:</p><ul><li>Copiar, reproducir, descargar o distribuir contenidos sin autorización</li><li>Modificar, adaptar, traducir o crear obras derivadas</li><li>Realizar ingeniería inversa o descompilación de código</li><li>Extraer o reutilizar contenidos para fines comerciales</li><li>Registrar como dominio o marca cualquier contenido del Sitio</li></ul><h2>6. Licencia Limitada de Uso</h2><p>La Empresa te otorga una licencia limitada, no exclusiva, revocable e intransferible para:</p><ul><li>Acceder y visualizar los contenidos para uso personal y no comercial</li><li>Imprimir artículos para uso privado (no para distribución pública)</li><li>Compartir enlaces al Sitio (no los contenidos descargados)</li></ul><p>Esta licencia puede ser revocada en cualquier momento sin notificación previa.</p><h2>7. Conducta Prohibida del Usuario</h2><p>Los usuarios se comprometen a NO:</p><ul><li>Violar leyes, regulaciones o derechos de terceros</li><li>Enviar contenido ilícito, amenazas, difamatorio o discriminatorio</li><li>Acosar, intimidar o amenazar a otros usuarios o empleados</li><li>Intentar acceso no autorizado a sistemas, redes o datos</li><li>Realizar actividades de spam, phishing, malware o ciberataques</li><li>Falsificar identidad o información personal</li><li>Interferir con la funcionalidad del Sitio (inyección SQL, scripts maliciosos, etc.)</li><li>Realizar scraping automatizado, minería de datos o recopilación masiva de datos</li><li>Usar bots, crawlers o herramientas de automatización no autorizadas</li></ul><p>La Empresa se reserva el derecho de suspender o eliminar cuentas de usuarios que violen estas normas.</p><h2>8. Limitación de Responsabilidad</h2><p>En la máxima medida permitida por la ley, la Empresa NO será responsable de:</p><ul><li>Daños directos, indirectos, incidentales, especiales, ejemplares o consecuentes</li><li>Pérdida de beneficios, ingresos, datos o reputación</li><li>Interrupciones del servicio o falta de disponibilidad</li><li>Errores, omisiones o inexactitudes en los contenidos</li><li>Acceso no autorizado a sistemas o información personal</li><li>Conducta de otros usuarios o terceros</li><li>Enlaces a sitios web externos o contenido de terceros</li></ul><p>ESTA LIMITACIÓN APLICA INCLUSO SI LA EMPRESA HA SIDO NOTIFICADA DE LA POSIBILIDAD DE TALES DAÑOS.</p><h2>9. Garantías</h2><p>EL SITIO SE PROPORCIONA "TAL CUAL" Y "SEGÚN DISPONIBILIDAD", SIN GARANTÍAS DE NINGÚN TIPO, EXPLÍCITAS O IMPLÍCITAS, INCLUYENDO:</p><ul><li>Garantías de comerciabilidad o idoneidad para un fin particular</li><li>Garantía de no infracción de derechos de terceros</li><li>Garantía de seguridad o ausencia de vulnerabilidades</li></ul><h2>10. Política de Privacidad y Datos</h2><p>El uso de datos personales se rige por nuestra Política de Privacidad. Al usar el Sitio, aceptas la recopilación y tratamiento de datos conforme a dicha política. Por favor, revísala cuidadosamente.</p><h2>11. Enlaces Externos</h2><p>El Sitio puede contener enlaces a sitios web de terceros. La Empresa:</p><ul><li>No controla ni es responsable por contenidos externos</li><li>No respalda necesariamente a terceros o sus políticas</li><li>No es responsable de disponibilidad o exactitud de enlaces</li></ul><p>La navegación a sitios externos es bajo tu propio riesgo. Te recomendamos revisar sus términos y políticas.</p><h2>12. Indemnización</h2><p>Aceptas indemnizar, defender y eximir de responsabilidad a [TU_NOMBRE_EMPRESA], sus empleados, directores, accionistas y agentes de cualquier reclamación, demanda, daño, pérdida, costo o gasto (incluyendo honorarios legales) derivados de:</p><ul><li>Tu violación de estos Términos</li><li>Tu uso del Sitio</li><li>Tu violación de cualquier ley o derecho de terceros</li><li>Contenido que envíes o transmitas</li></ul><h2>13. Modificación de Términos</h2><p>La Empresa se reserva el derecho de modificar estos Términos en cualquier momento. Los cambios entrarán en vigor inmediatamente tras su publicación. Tu uso continuado del Sitio implica aceptación de cualquier cambio.</p><h2>14. Terminación</h2><p>La Empresa puede suspender o terminar tu acceso al Sitio en cualquier momento, sin notificación previa y sin causa justificada, especialmente si:</p><ul><li>Violas estos Términos</li><li>Cometes actividades ilegales o fraudulentas</li><li>Incumples pagos (si aplica)</li></ul><h2>15. Divisibilidad</h2><p>Si alguna disposición de estos Términos es declarada inválida, ilegal o inaplicable, dicha disposición será modificada al mínimo necesario o eliminada, sin afectar la validez del resto de los Términos.</p><h2>16. Ley Aplicable y Jurisdicción</h2><p>Estos Términos se rigen por la ley española, sin consideración de conflictos de leyes. Cualquier disputa será resuelta exclusivamente por los juzgados competentes de Madrid, España. Ambas partes se someten voluntariamente a esta jurisdicción.</p><p><strong>Última actualización:</strong> 14 de julio de 2026</p>'],
            'en' => ['content' => '<h2>1. Object and Scope</h2><p>These Terms of Service ("Terms") regulate access and use of the website www.example.com (the "Site") and all services, contents, and functionalities available therein, operated by [TU_NOMBRE_EMPRESA] (the "Company").</p><h2>2. Acceptance of Terms</h2><p>By accessing and using this Site, you fully and unconditionally accept all Terms, Privacy Policy, Cookie Policy, and other available policies. If you disagree with any provision, you must stop using the Site immediately.</p><h2>3. Users and Legal Capacity</h2><p>Use of this Site is permitted only to persons with legal capacity to contract. If you are under 18 years old, you require express parental or guardian consent. The Company reserves the right to verify user identity and age.</p><h2>4. Site Access and Availability</h2><p>The Company strives to maintain Site availability 24/7, but does not guarantee:</p><ul><li>Uninterrupted availability</li><li>Absence of technical or security errors</li><li>Accuracy, correctness, or timeliness of contents</li><li>Compatibility with all browsers or devices</li></ul><p>The Company reserves the right to perform maintenance, updates, or suspend service without notice.</p><h2>5. Intellectual and Industrial Property</h2><p>All Site contents, including but not limited to:</p><ul><li>Texts, articles, and descriptions</li><li>Images, photographs, graphics, and videos</li><li>Site design, layout, and structure</li><li>Trademarks, logos, and distinctive signs</li><li>Source code, scripts, and web applications</li><li>Databases and data compilations</li></ul><p>...are exclusive property of the Company or its licensors and are protected by international intellectual and industrial property laws, including Spanish Intellectual Property Law (LPI).</p><p>The following are expressly prohibited:</p><ul><li>Copying, reproducing, downloading, or distributing contents without authorization</li><li>Modifying, adapting, translating, or creating derivative works</li><li>Performing reverse engineering or decompilation of code</li><li>Extracting or reusing contents for commercial purposes</li><li>Registering as domain or trademark any Site content</li></ul><h2>6. Limited License of Use</h2><p>The Company grants you a limited, non-exclusive, revocable, and non-transferable license to:</p><ul><li>Access and view contents for personal and non-commercial use</li><li>Print articles for private use (not for public distribution)</li><li>Share links to the Site (not downloaded contents)</li></ul><p>This license may be revoked at any time without prior notice.</p><h2>7. Prohibited User Conduct</h2><p>Users commit to NOT:</p><ul><li>Violate laws, regulations, or third-party rights</li><li>Send illegal, threatening, defamatory, or discriminatory content</li><li>Harass, intimidate, or threaten other users or employees</li><li>Attempt unauthorized access to systems, networks, or data</li><li>Conduct spam, phishing, malware, or cyberattacks</li><li>Falsify identity or personal information</li><li>Interfere with Site functionality (SQL injection, malicious scripts, etc.)</li><li>Perform automated scraping, data mining, or mass data collection</li><li>Use unauthorized bots, crawlers, or automation tools</li></ul><p>The Company reserves the right to suspend or delete user accounts that violate these rules.</p><h2>8. Limitation of Liability</h2><p>To the maximum extent permitted by law, the Company is NOT liable for:</p><ul><li>Direct, indirect, incidental, special, exemplary, or consequential damages</li><li>Loss of profits, revenue, data, or reputation</li><li>Service interruptions or unavailability</li><li>Errors, omissions, or inaccuracies in contents</li><li>Unauthorized access to systems or personal information</li><li>Conduct of other users or third parties</li><li>Links to external websites or third-party content</li></ul><p>THIS LIMITATION APPLIES EVEN IF THE COMPANY HAS BEEN NOTIFIED OF THE POSSIBILITY OF SUCH DAMAGES.</p><h2>9. Warranties</h2><p>THE SITE IS PROVIDED "AS IS" AND "AS AVAILABLE," WITHOUT WARRANTIES OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING:</p><ul><li>Warranties of merchantability or fitness for a particular purpose</li><li>Warranty of non-infringement of third-party rights</li><li>Warranty of security or absence of vulnerabilities</li></ul><h2>10. Privacy Policy and Data</h2><p>The use of personal data is governed by our Privacy Policy. By using the Site, you accept data collection and processing in accordance with that policy. Please review it carefully.</p><h2>11. External Links</h2><p>The Site may contain links to third-party websites. The Company:</p><ul><li>Does not control or is responsible for external contents</li><li>Does not necessarily endorse third parties or their policies</li><li>Is not responsible for link availability or accuracy</li></ul><p>Navigation to external sites is at your own risk. We recommend reviewing their terms and policies.</p><h2>12. Indemnification</h2><p>You agree to indemnify, defend, and hold harmless [TU_NOMBRE_EMPRESA], its employees, directors, shareholders, and agents from any claim, lawsuit, damage, loss, cost, or expense (including legal fees) arising from:</p><ul><li>Your violation of these Terms</li><li>Your use of the Site</li><li>Your violation of any law or third-party rights</li><li>Content you submit or transmit</li></ul><h2>13. Modification of Terms</h2><p>The Company reserves the right to modify these Terms at any time. Changes will take effect immediately upon posting. Your continued use of the Site implies acceptance of any changes.</p><h2>14. Termination</h2><p>The Company may suspend or terminate your Site access at any time, without notice and without cause, especially if you:</p><ul><li>Violate these Terms</li><li>Commit illegal or fraudulent activities</li><li>Default on payments (if applicable)</li></ul><h2>15. Severability</h2><p>If any provision of these Terms is declared invalid, illegal, or unenforceable, that provision will be modified to the minimum extent necessary or removed, without affecting the validity of the remaining Terms.</p><h2>16. Applicable Law and Jurisdiction</h2><p>These Terms are governed by Spanish law, without consideration of conflict of laws. Any dispute shall be resolved exclusively by competent courts in Madrid, Spain. Both parties voluntarily submit to this jurisdiction.</p><p><strong>Last Updated:</strong> July 14, 2026</p>']
        ], $langIds);


        // ── 6. Portal de Transparencia (transparency) ───────────────────────
        $transparencyId = $this->upsertLegalPage('transparencia', 'generic', [
            'status'             => 'published',
            'published_at'       => $now,
            'scheduled_at'       => null,
            'sort_order'         => 150,
            'sitemap_priority'   => '0.4',
            'sitemap_changefreq' => 'monthly',
            'is_in_sitemap'      => 1,
        ]);
        $this->upsertPageTranslation($transparencyId, $langIds['es'], [
            'slug'             => 'transparencia',
            'title'            => 'Portal de Transparencia',
            'excerpt'          => 'Publicación de estatutos, informes y memorias de cumplimiento público.',
            'meta_title'       => 'Portal de Transparencia | Mi Sitio',
            'meta_description' => 'Conozca nuestras cuentas anuales, estatutos y documentos corporativos.',
            'canonical_url'    => null,
            'robots'           => 'index, follow',
            'schema_data'      => null,
        ]);
        $this->upsertPageTranslation($transparencyId, $langIds['en'], [
            'slug'             => 'transparency',
            'title'            => 'Transparency Portal',
            'excerpt'          => 'Publication of corporate bylaws, annual accounts, and compliance reports.',
            'meta_title'       => 'Transparency Portal | My Site',
            'meta_description' => 'Learn about our annual reports, bylaws, and corporate documents.',
            'canonical_url'    => null,
            'robots'           => 'index, follow',
            'schema_data'      => null,
        ]);

        $this->resetPageBlocks($transparencyId);
        $this->upsertBlock($transparencyId, $blockIds, 'page_header', 1, [], [
            'es' => ['heading' => 'Portal de Transparencia', 'subheading' => 'Nuestro firme compromiso con el acceso a la información y el buen gobierno.'],
            'en' => ['heading' => 'Transparency Portal', 'subheading' => 'Our strong commitment to access to information and good governance.']
        ], $langIds);
        $this->upsertBlock($transparencyId, $blockIds, 'rich_text', 2, [], [
            'es' => ['content' => '<h2>Buen Gobierno y Cumplimiento</h2><p>Ponemos a disposición pública la documentación organizativa, económica e institucional con el fin de facilitar la transparencia y la rendición de cuentas ante la sociedad.</p>'],
            'en' => ['content' => '<h2>Good Governance & Compliance</h2><p>We make organizational, economic, and institutional documentation publicly available in order to facilitate transparency and accountability to society.</p>']
        ], $langIds);
        $this->upsertBlock($transparencyId, $blockIds, 'document_gallery', 3, ['layout' => 'grid_cards'], [
            'es' => [
                'title' => 'Documentación Descargable',
                'description' => 'Estatutos e informes financieros anuales.',
                'documents' => [
                    [
                        'file_url' => 'https://example.com/docs/estatutos_corporativos.pdf',
                        'title' => 'Estatutos de la Entidad',
                        'description' => 'Documentación fundacional y estatutos vigentes del Starter CMS.'
                    ],
                    [
                        'file_url' => 'https://example.com/docs/cuentas_anuales_2025.xlsx',
                        'title' => 'Cuentas Anuales 2025',
                        'description' => 'Cierre financiero y auditorías contables aprobadas.'
                    ]
                ]
            ],
            'en' => [
                'title' => 'Downloadable Documents',
                'description' => 'Bylaws and annual financial reports.',
                'documents' => [
                    [
                        'file_url' => 'https://example.com/docs/corporate_bylaws.pdf',
                        'title' => 'Entity Bylaws',
                        'description' => 'Foundational documents and current bylaws of Starter CMS.'
                    ],
                    [
                        'file_url' => 'https://example.com/docs/annual_accounts_2025.xlsx',
                        'title' => 'Annual Accounts 2025',
                        'description' => 'Financial closing statement and approved audits.'
                    ]
                ]
            ]
        ], $langIds);


        // ── 7. Accesibilidad (accessibility) ───────────────────────────────
        $accessibilityId = $this->upsertLegalPage('accesibilidad', 'generic', [
            'status'             => 'published',
            'published_at'       => $now,
            'scheduled_at'       => null,
            'sort_order'         => 160,
            'sitemap_priority'   => '0.3',
            'sitemap_changefreq' => 'monthly',
            'is_in_sitemap'      => 1,
        ]);
        $this->upsertPageTranslation($accessibilityId, $langIds['es'], [
            'slug'             => 'accesibilidad',
            'title' => 'Accesibilidad',
            'excerpt' => 'Declaración sobre la conformidad del sitio web con pautas WCAG de accesibilidad.',
            'meta_title' => 'Declaración de Accesibilidad | Mi Sitio',
            'meta_description' => 'Compromiso de accesibilidad web para garantizar la navegación a todos.',
            'canonical_url' => null,
            'robots' => 'index, follow',
            'schema_data' => null,
        ]);
        $this->upsertPageTranslation($accessibilityId, $langIds['en'], [
            'slug'             => 'accessibility',
            'title' => 'Accessibility',
            'excerpt' => 'Statement on the compliance of this website with WCAG accessibility guidelines.',
            'meta_title' => 'Accessibility Statement | My Site',
            'meta_description' => 'Web accessibility commitment to ensure navigation for everyone.',
            'canonical_url' => null,
            'robots' => 'index, follow',
            'schema_data' => null,
        ]);

        $this->resetPageBlocks($accessibilityId);
        $this->upsertBlock($accessibilityId, $blockIds, 'page_header', 1, [], [
            'es' => ['heading' => 'Declaración de Accesibilidad', 'subheading' => 'Nuestro compromiso con la accesibilidad web conforme a WCAG 2.1 AA.'],
            'en' => ['heading' => 'Accessibility Statement', 'subheading' => 'Our commitment to web accessibility in compliance with WCAG 2.1 AA.']
        ], $langIds);
        $this->upsertBlock($accessibilityId, $blockIds, 'rich_text', 2, [], [
            'es' => ['content' => '<h2>Declaración de Accesibilidad del Sitio Web</h2><p>[TU_NOMBRE_EMPRESA] está comprometida con la accesibilidad web. Esta Declaración de Accesibilidad explica el estado actual de accesibilidad de nuestro sitio web.</p><h2>Estado de Cumplimiento</h2><p>Este sitio web (www.ejemplo.es) se ha diseñado y desarrollado con la intención de ser <strong>parcialmente conforme</strong> con las Pautas de Accesibilidad para el Contenido Web (WCAG) 2.1, nivel AA. El contenido y la funcionalidad están optimizados para ser accesibles a personas con discapacidades diversas, incluyendo:</p><ul><li>Discapacidad visual (ceguera, baja visión, daltonismo)</li><li>Discapacidad auditiva (sordera, hipoacusia)</li><li>Discapacidad motora (movilidad reducida, control motor limitado)</li><li>Discapacidad cognitiva (dislexia, TDAH, autismo)</li></ul><h2>Características de Accesibilidad Implementadas</h2><h3>Navegación y Estructura</h3><ul><li>Navegación clara y consistente en todas las páginas</li><li>Encabezados semántticos (h1, h2, h3, etc.) con jerarquía lógica</li><li>Saltos de navegación ("Skip to main content")</li><li>Estructura de página predecible y lógica</li></ul><h3>Contenido Visual</h3><ul><li>Suficiente contraste de color entre texto y fondo (ratio 4.5:1 para texto normal)</li><li>Textos alternativos descriptivos para todas las imágenes (atributo alt)</li><li>Contenido no dependiente exclusivamente del color</li><li>Imágenes de fondo no esenciales pero con información de contexto</li></ul><h3>Formularios</h3><ul><li>Etiquetas de formulario asociadas correctamente a los controles (atributo label for)</li><li>Indicación clara de campos requeridos</li><li>Mensajes de error descriptivos y accesibles</li><li>Navegación de formulario con teclado</li></ul><h3>Multimedia</h3><ul><li>Subtítulos para vídeos (cuando aplica)</li><li>Transcripciones para contenido de audio</li><li>Controles multimedia accesibles</li></ul><h3>Navegación por Teclado</h3><ul><li>Todos los elementos interactivos son accesibles mediante teclado</li><li>Indicador visual de foco clara y visible</li><li>Orden de tabulación lógico</li><li>Sin trampas de teclado que impidan escapar de elementos</li></ul><h3>Escritura Clara</h3><ul><li>Lenguaje simple y claro</li><li>Párrafos y listas cortos y bien estructurados</li><li>Evitamos abreviaturas confusas sin explicación</li><li>Definición de términos técnicos poco comunes</li></ul><h2>Estándares Técnicos Cumplidos</h2><ul><li><strong>WCAG 2.1 Nivel A:</strong> Criterios básicos de accesibilidad (totalmente implementado)</li><li><strong>WCAG 2.1 Nivel AA:</strong> Criterios intermedios (parcialmente implementado)</li><li><strong>HTML5 Semántico:</strong> Uso correcto de elementos semánticos</li><li><strong>ARIA (Accessible Rich Internet Applications):</strong> Atributos de accesibilidad para elementos dinámicos</li><li><strong>Directiva de Accesibilidad de la UE 2016/2102:</strong> Cumplimiento obligatorio (España)</li></ul><h2>Limitaciones Conocidas de Accesibilidad</h2><p>Aunque nos esforzamos por ser totalmente accesibles, podemos tener limitaciones en:</p><ul><li><strong>Contenido de Terceros:</strong> Enlaces externos y contenido embebido de otros sitios pueden no cumplir WCAG 2.1</li><li><strong>PDFs Antiguos:</strong> Algunos documentos PDF descargables pueden no ser totalmente accesibles</li><li><strong>Vídeos sin Subtítulos:</strong> Algunos vídeos pueden estar sin subtítulos aún</li><li><strong>Formularios Complejos:</strong> Algunos formularios avanzados pueden tener barreras de accesibilidad</li></ul><h2>Mejoras Previstas</h2><p>Continuamos trabajando para mejorar la accesibilidad del sitio. Las mejoras planeadas incluyen:</p><ul><li>Subtitulación de todo el contenido de vídeo</li><li>Mejora de contraste en algunos elementos del diseño</li><li>Optimización de formularios avanzados</li><li>Auditoría y remediación de PDFs</li></ul><h2>Tecnologías Asistivas Probadas</h2><p>Este sitio ha sido probado con las siguientes tecnologías asistivas:</p><ul><li><strong>NVDA</strong> (NonVisual Desktop Access) - lector de pantalla gratuito</li><li><strong>JAWS</strong> (Job Access With Speech) - lector de pantalla comercial</li><li><strong>Navegación por Teclado</strong> - sin necesidad de ratón</li><li><strong>Zoom del Navegador</strong> - escalado hasta 200%</li></ul><h2>Cómo Usar Tecnologías Asistivas</h2><h3>Lectores de Pantalla</h3><p>Si usas un lector de pantalla, este sitio debería ser completamente funcional. Los elementos están correctamente etiquetados y anunciados.</p><h3>Navegación por Teclado</h3><ul><li><strong>Tab:</strong> Navegar al siguiente elemento interactivo</li><li><strong>Shift + Tab:</strong> Navegar al elemento anterior</li><li><strong>Enter:</strong> Activar botones y enlaces</li><li><strong>Flecha Arriba/Abajo:</strong> Seleccionar opciones en menús desplegables</li><li><strong>Espacio:</strong> Activar casillas de verificación y botones radio</li></ul><h3>Ajustes del Navegador</h3><p>La mayoría de navegadores ofrecen opciones de accesibilidad:</p><ul><li><strong>Aumentar tamaño de fuente:</strong> Ctrl + (Chrome, Firefox, Edge)</li><li><strong>Zoom de página:</strong> Ctrl + + para aumentar hasta 200%</li><li><strong>Modo Lectura:</strong> Disponible en Firefox y Safari</li><li><strong>Filtros de Color:</strong> Para usuarios con daltonismo</li></ul><h2>Contacto para Problemas de Accesibilidad</h2><p>Si encuentras barreras de accesibilidad o deseas reportar un problema:</p><ul><li><strong>Email:</strong> [TU_EMAIL_ACCESIBILIDAD]</li><li><strong>Teléfono:</strong> [Número de contacto] (de lunes a viernes, 9:00-18:00 CET)</li><li><strong>Formulario de Contacto:</strong> Disponible en la página de <a href="/es/contacto">Contacto</a></li></ul><p>Por favor, proporciona:</p><ul><li>Descripción detallada del problema</li><li>URL de la página afectada</li><li>Dispositivo y navegador utilizado</li><li>Tecnología asistiva empleada (si aplica)</li></ul><p>Responderemos a tu solicitud en un plazo de 2-3 días hábiles.</p><h2>Procedimiento de Reclamación</h2><p>Si no estás satisfecho con nuestra respuesta, puedes presentar una reclamación ante:</p><ul><li><strong>España:</strong> <strong>Dirección General de Telecomunicaciones</strong> (Ministerio de Asuntos Económicos y Transformación Digital) - <a href="https://www.dgt.gob.es">www.dgt.gob.es</a></li></ul><h2>Recursos de Accesibilidad Web</h2><p>Para más información sobre accesibilidad web, consulta:</p><ul><li><a href="https://www.w3.org/WAI/" target="_blank">Web Accessibility Initiative (WAI)</a></li><li><a href="https://www.w3.org/TR/WCAG21/" target="_blank">WCAG 2.1 Guidelines (W3C)</a></li><li><a href="https://www.boe.es/eli/es/rd/2023/09/30/1112" target="_blank">Real Decreto Español 1112/2023 sobre Accesibilidad</a></li></ul><h2>Política de Accesibilidad</h2><p>La accesibilidad es un valor fundamental en nuestra organización. Nos comprometemos a:</p><ul><li>Mantener y mejorar continuamente la accesibilidad del sitio</li><li>Proporcionar contenido accesible en todos los formatos</li><li>Escuchar y responder rápidamente a problemas de accesibilidad</li><li>Capacitar a nuestro equipo en mejores prácticas de accesibilidad</li><li>Realizar auditorías de accesibilidad regularmente</li></ul><p><strong>Última actualización:</strong> 14 de julio de 2026</p>'],
            'en' => ['content' => '<h2>Website Accessibility Statement</h2><p>[TU_NOMBRE_EMPRESA] is committed to web accessibility. This Accessibility Statement explains the current accessibility status of our website.</p><h2>Compliance Status</h2><p>This website (www.example.com) has been designed and developed with the intention to be <strong>partially conformant</strong> with the Web Content Accessibility Guidelines (WCAG) 2.1, Level AA. Content and functionality are optimized to be accessible to people with diverse disabilities, including:</p><ul><li>Visual disabilities (blindness, low vision, color blindness)</li><li>Hearing disabilities (deafness, hearing impairment)</li><li>Motor disabilities (limited mobility, motor control limitations)</li><li>Cognitive disabilities (dyslexia, ADHD, autism)</li></ul><h2>Implemented Accessibility Features</h2><h3>Navigation and Structure</h3><ul><li>Clear and consistent navigation across all pages</li><li>Semantic headings (h1, h2, h3, etc.) with logical hierarchy</li><li>Skip navigation links ("Skip to main content")</li><li>Predictable and logical page structure</li></ul><h3>Visual Content</h3><ul><li>Sufficient color contrast between text and background (4.5:1 ratio for normal text)</li><li>Descriptive alternative text for all images (alt attribute)</li><li>Content not dependent exclusively on color</li><li>Background images non-essential but with contextual information</li></ul><h3>Forms</h3><ul><li>Form labels correctly associated with controls (label for attribute)</li><li>Clear indication of required fields</li><li>Descriptive and accessible error messages</li><li>Form navigation via keyboard</li></ul><h3>Multimedia</h3><ul><li>Captions for videos (where applicable)</li><li>Transcripts for audio content</li><li>Accessible multimedia controls</li></ul><h3>Keyboard Navigation</h3><ul><li>All interactive elements accessible via keyboard</li><li>Clear and visible focus indicator</li><li>Logical tab order</li><li>No keyboard traps preventing escape from elements</li></ul><h3>Clear Writing</h3><ul><li>Simple and clear language</li><li>Short and well-structured paragraphs and lists</li><li>Avoidance of confusing abbreviations without explanation</li><li>Definition of uncommon technical terms</li></ul><h2>Technical Standards Met</h2><ul><li><strong>WCAG 2.1 Level A:</strong> Basic accessibility criteria (fully implemented)</li><li><strong>WCAG 2.1 Level AA:</strong> Intermediate criteria (partially implemented)</li><li><strong>Semantic HTML5:</strong> Correct use of semantic elements</li><li><strong>ARIA:</strong> Accessibility attributes for dynamic elements</li><li><strong>EU Accessibility Directive 2016/2102:</strong> Mandatory compliance (Spain)</li></ul><h2>Known Accessibility Limitations</h2><p>While we strive to be fully accessible, we may have limitations in:</p><ul><li><strong>Third-Party Content:</strong> External links and embedded content from other sites may not comply with WCAG 2.1</li><li><strong>Legacy PDFs:</strong> Some downloadable PDF documents may not be fully accessible</li><li><strong>Videos Without Captions:</strong> Some videos may lack captions</li><li><strong>Complex Forms:</strong> Some advanced forms may have accessibility barriers</li></ul><h2>Planned Improvements</h2><p>We continue working to improve site accessibility. Planned improvements include:</p><ul><li>Captioning of all video content</li><li>Improved contrast in design elements</li><li>Optimization of advanced forms</li><li>Audit and remediation of PDFs</li></ul><h2>Assistive Technologies Tested</h2><p>This site has been tested with the following assistive technologies:</p><ul><li><strong>NVDA</strong> (NonVisual Desktop Access) - free screen reader</li><li><strong>JAWS</strong> (Job Access With Speech) - commercial screen reader</li><li><strong>Keyboard Navigation</strong> - without mouse use</li><li><strong>Browser Zoom</strong> - scaling up to 200%</li></ul><h2>How to Use Assistive Technologies</h2><h3>Screen Readers</h3><p>If you use a screen reader, this site should be fully functional. Elements are correctly labeled and announced.</p><h3>Keyboard Navigation</h3><ul><li><strong>Tab:</strong> Navigate to the next interactive element</li><li><strong>Shift + Tab:</strong> Navigate to the previous element</li><li><strong>Enter:</strong> Activate buttons and links</li><li><strong>Arrow Up/Down:</strong> Select options in dropdown menus</li><li><strong>Space:</strong> Activate checkboxes and radio buttons</li></ul><h3>Browser Settings</h3><p>Most browsers offer accessibility options:</p><ul><li><strong>Increase Font Size:</strong> Ctrl + (Chrome, Firefox, Edge)</li><li><strong>Page Zoom:</strong> Ctrl + + to increase up to 200%</li><li><strong>Reader Mode:</strong> Available in Firefox and Safari</li><li><strong>Color Filters:</strong> For color-blind users</li></ul><h2>Contact for Accessibility Issues</h2><p>If you encounter accessibility barriers or wish to report an issue:</p><ul><li><strong>Email:</strong> accessibility@example.com</li><li><strong>Phone:</strong> [Contact Number] (Monday-Friday, 9:00-18:00 CET)</li><li><strong>Contact Form:</strong> Available on our <a href="/en/contact">Contact</a> page</li></ul><p>Please provide:</p><ul><li>Detailed description of the problem</li><li>URL of the affected page</li><li>Device and browser used</li><li>Assistive technology employed (if applicable)</li></ul><p>We will respond to your request within 2-3 business days.</p><h2>Complaint Procedure</h2><p>If you are not satisfied with our response, you can file a complaint with:</p><ul><li><strong>Spain:</strong> <strong>General Directorate of Telecommunications</strong> (Ministry of Economic Affairs and Digital Transformation) - <a href="https://www.dgt.gob.es">www.dgt.gob.es</a></li></ul><h2>Web Accessibility Resources</h2><p>For more information about web accessibility, see:</p><ul><li><a href="https://www.w3.org/WAI/" target="_blank">Web Accessibility Initiative (WAI)</a></li><li><a href="https://www.w3.org/TR/WCAG21/" target="_blank">WCAG 2.1 Guidelines (W3C)</a></li><li><a href="https://www.boe.es/eli/es/rd/2023/09/30/1112" target="_blank">Spanish Royal Decree 1112/2023 on Accessibility</a></li></ul><h2>Accessibility Policy</h2><p>Accessibility is a core value in our organization. We commit to:</p><ul><li>Continuously maintaining and improving site accessibility</li><li>Providing accessible content in all formats</li><li>Quickly listening and responding to accessibility issues</li><li>Training our team in accessibility best practices</li><li>Conducting regular accessibility audits</li></ul><p><strong>Last Updated:</strong> July 14, 2026</p>']
        ], $langIds);

        echo "SiteLegalPagesSeeder: all legal and transparency pages seeded successfully.\n";
    }

    // ── Helper functions adapted for generic and singleton pages ───────────

    private function upsertLegalPage(string $defaultSlug, string $pageType, array $pageData): int
    {
        // 1. Check if page translation with this slug already exists for the default language
        $existing = $this->db->table('cms_page_translations')
            ->where('slug', $defaultSlug)
            ->get()
            ->getRowArray();

        if ($existing !== null) {
            $pageId = (int) $existing['page_id'];
            $this->db->table('cms_pages')
                ->where('id', $pageId)
                ->update(array_merge($pageData, [
                    'page_type' => $pageType,
                ]));
            return $pageId;
        }

        // 2. Also check by page_type if it is a singleton type
        if (in_array($pageType, ['privacy', 'terms'], true)) {
            $existingSingleton = $this->db->table('cms_pages')
                ->where('page_type', $pageType)
                ->where('deleted_at IS NULL')
                ->get()
                ->getRowArray();
            if ($existingSingleton !== null) {
                $pageId = (int) $existingSingleton['id'];
                $this->db->table('cms_pages')
                    ->where('id', $pageId)
                    ->update(array_merge($pageData, [
                        'page_type' => $pageType,
                    ]));
                return $pageId;
            }
        }

        // 3. Otherwise insert new page
        $this->db->table('cms_pages')->insert(array_merge($pageData, [
            'page_type' => $pageType,
        ]));

        return (int) $this->db->insertID();
    }

    /** @param array<string, mixed> $translationData */
    private function upsertPageTranslation(int $pageId, int $languageId, array $translationData): void
    {
        $slug = (string) ($translationData['slug'] ?? '');
        if ($slug !== '') {
            $conflict = $this->db->table('cms_page_translations')
                ->where('language_id', $languageId)
                ->where('slug', $slug)
                ->get()
                ->getRowArray();
            if ($conflict !== null && (int) $conflict['page_id'] !== $pageId) {
                // Don't update or crash on slug conflict, skip
                return;
            }
        }

        $this->upsertRecord('cms_page_translations', [
            'page_id'     => $pageId,
            'language_id' => $languageId,
        ], $translationData);
    }

    /**
     * @param array<string, int>                  $blockIds
     * @param array<string, mixed>                $config
     * @param array<string, array<string, mixed>> $translations
     * @param array<string, int>                  $langIds
     */
    private function upsertBlock(
        int    $pageId,
        array  $blockIds,
        string $blockKey,
        int    $sortOrder,
        array  $config,
        array  $translations,
        array  $langIds,
        ?int   $parentInstanceId = null
    ): int {
        $blockId = $blockIds[$blockKey] ?? null;
        if ($blockId === null) {
            echo "SiteLegalPagesSeeder: block type '{$blockKey}' not found — skipped.\n";
            return 0;
        }

        $instanceId = $this->upsertRecord('cms_block_instances', [
            'block_id'           => $blockId,
            'owner_type'         => 'page',
            'owner_id'           => $pageId,
            'parent_instance_id' => $parentInstanceId,
            'sort_order'         => $sortOrder,
        ], [
            'column_index'       => null,
            'is_active'          => 1,
            'block_config'       => json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        foreach ($translations as $langCode => $data) {
            $langId = $langIds[$langCode] ?? null;
            if ($langId === null || ! is_array($data) || $data === []) {
                continue;
            }
            $this->upsertTranslation($instanceId, $langId, $data);
        }

        return $instanceId;
    }

    /** @param array<string, mixed> $blockData */
    private function upsertTranslation(int $instanceId, int $languageId, array $blockData): void
    {
        $this->upsertRecord('cms_block_instance_translations', [
            'instance_id' => $instanceId,
            'language_id' => $languageId,
        ], [
            'block_data'   => json_encode($blockData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'is_published' => 1,
        ]);
    }

    private function resetPageBlocks(int $pageId): void
    {
        $instanceIds = $this->db->table('cms_block_instances')
            ->select('id')
            ->where('owner_type', 'page')
            ->where('owner_id', $pageId)
            ->get()
            ->getResultArray();

        if ($instanceIds === []) {
            return;
        }

        $ids = array_map(static fn (array $row): int => (int) $row['id'], $instanceIds);
        $this->db->table('cms_block_instance_translations')->whereIn('instance_id', $ids)->delete();
        $this->db->table('cms_block_instances')->whereIn('id', $ids)->delete();
    }

    /** @param string[] $keys  @return array<string, int> */
    private function blockIds(array $keys): array
    {
        $rows = $this->db->table('cms_content_blocks')
            ->whereIn('block_key', $keys)
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $map[$row['block_key']] = (int) $row['id'];
        }

        return $map;
    }

    /** @param string[] $codes  @return array<string, int> */
    private function langIds(array $codes): array
    {
        $rows = $this->db->table('cms_languages')
            ->whereIn('code', $codes)
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $map[$row['code']] = (int) $row['id'];
        }

        return $map;
    }

    /**
     * Automatically migrate old legal page slugs to new ones.
     * This ensures the seeder is fully idempotent even if page slugs change between runs.
     * If old pages exist and new pages don't, renames old→new.
     * If both exist, deletes the old to avoid duplicates.
     */
    private function migrateOldLegalPages(): void
    {
        // Legacy slug → current slug mapping (from prior naming conventions)
        $slugMappings = [
            'legal-notice' => 'aviso-legal',
            'privacy-policy' => 'politica-privacidad',
            'cookie-policy' => 'politica-cookies',
            'data-rights' => 'derechos-datos',
            'terms-of-service' => 'terminos-servicio',
            'transparency' => 'transparencia',
            'accessibility' => 'accesibilidad',
        ];

        foreach ($slugMappings as $oldSlug => $newSlug) {
            $oldPage = $this->db->table('cms_page_translations')
                ->where('slug', $oldSlug)
                ->get()
                ->getRowArray();

            if ($oldPage === null) {
                continue; // Old page doesn't exist, nothing to migrate
            }

            $oldPageId = (int) $oldPage['page_id'];

            // Check if new page already exists
            $newPage = $this->db->table('cms_page_translations')
                ->where('slug', $newSlug)
                ->get()
                ->getRowArray();

            if ($newPage !== null) {
                // New page exists → delete old to avoid duplicates
                $this->deletePageAndReferences($oldPageId);
                echo "SiteLegalPagesSeeder: deleted duplicate old page with slug '{$oldSlug}'.\n";
                continue;
            }

            // New page doesn't exist → rename old to new
            $this->db->table('cms_page_translations')
                ->where('page_id', $oldPageId)
                ->update(['slug' => $newSlug]);
            echo "SiteLegalPagesSeeder: migrated slug '{$oldSlug}' → '{$newSlug}'.\n";
        }
    }

    /**
     * Delete a page and all its references (blocks, translations, menu items).
     * Performs soft delete on the page itself (deleted_at timestamp).
     */
    private function deletePageAndReferences(int $pageId): void
    {
        // Clean up block instances and their translations
        $this->resetPageBlocks($pageId);

        // Delete page translations
        $this->db->table('cms_page_translations')
            ->where('page_id', $pageId)
            ->delete();

        // Soft delete the page
        $this->db->table('cms_pages')
            ->where('id', $pageId)
            ->update(['deleted_at' => date('Y-m-d H:i:s')]);

        // Clean up menu items that referenced this page
        $this->db->table('cms_menu_items')
            ->where('page_id', $pageId)
            ->update(['page_id' => null]);
    }
}
