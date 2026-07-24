<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use App\Database\Seeds\Concerns\IdempotentSeederSupport;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Database\Seeder;

/**
 * Seeds legal pages for Chile - Ley 19.628 compliant
 * Follows Chilean data protection law and common practices
 */
class SiteLegalPagesSeederChile extends Seeder
{
    use IdempotentSeederSupport;


    public function run(): void
    {
        $langIds = $this->langIds(['es', 'en']);
        if (! isset($langIds['es'], $langIds['en'])) {
            echo "SiteLegalPagesSeederChile: Spanish and English languages required.\n";
            return;
        }

        $blockIds = $this->blockIds([
            'page_header', 'rich_text', 'anchor_nav', 'faq_accordion', 'form_embed'
        ]);

        $now = date('Y-m-d H:i:s');

        // ── 1. Aviso Legal Chile ──────────────────────────────────────
        $legalNoticeId = $this->upsertPage('aviso-legal', 'generic', [
            'status'             => 'published',
            'published_at'       => $now,
            'sort_order'         => 100,
            'sitemap_priority'   => '0.3',
            'is_in_sitemap'      => 1,
        ]);
        $this->upsertPageTranslation($legalNoticeId, $langIds['es'], [
            'slug'             => 'aviso-legal',
            'title'            => 'Aviso Legal',
            'excerpt'          => 'Información legal del propietario del sitio según normativa chilena.',
            'meta_title'       => 'Aviso Legal | Mi Sitio Demo SpA',
            'meta_description' => 'Datos identificativos y legal del sitio web.',
            'canonical_url'    => null,
            'robots'           => 'index, follow',
            'schema_data'      => null,
        ]);
        $this->upsertPageTranslation($legalNoticeId, $langIds['en'], [
            'slug'             => 'legal-notice',
            'title'            => 'Legal Notice',
            'excerpt'          => 'Legal information about the website operator.',
            'meta_title'       => 'Legal Notice | My Demo Site LLC',
            'meta_description' => 'Website legal notice and ownership information.',
            'canonical_url'    => null,
            'robots'           => 'index, follow',
            'schema_data'      => null,
        ]);

        $this->resetPageBlocks($legalNoticeId);
        $this->upsertBlock($legalNoticeId, $blockIds, 'page_header', 1, [], [
            'es' => ['heading' => 'Aviso Legal', 'subheading' => 'Información sobre el propietario y operación del sitio'],
            'en' => ['heading' => 'Legal Notice', 'subheading' => 'Information about the website operator and operations']
        ], $langIds);
        $this->upsertBlock($legalNoticeId, $blockIds, 'rich_text', 2, [], [
            'es' => ['content' => '<h2>1. Datos Identificativos</h2><p>En cumplimiento con la Ley 19.628 sobre Protección de Datos de Carácter Personal, se proporciona la siguiente información:</p><ul><li><strong>Nombre o Razón Social:</strong> Mi Sitio Demo SpA</li><li><strong>RUT:</strong> 76.123.456-7</li><li><strong>Domicilio:</strong> Avenida Providencia 1234, Región Metropolitana</li><li><strong>Teléfono:</strong> +56 2 2345 6789</li><li><strong>Email Contacto:</strong> contacto@example.com</li><li><strong>Giro Comercial:</strong> Servicios digitales</li></ul><h2>2. Aceptación de Términos</h2><p>El acceso y uso de este sitio web implica la aceptación íntegra de los términos y condiciones aquí establecidos. Si no está de acuerdo, debe abstenerse de usar este sitio.</p><h2>3. Propiedad Intelectual</h2><p>Todos los contenidos del sitio (textos, imágenes, diseños, logos, software) son propiedad intelectual de Mi Sitio Demo SpA o sus licenciantes, protegidos por la Ley 17.336 sobre Derechos de Autor. Queda prohibida su reproducción sin autorización.</p><h2>4. Limitación de Responsabilidad</h2><p>El sitio se proporciona "tal cual" sin garantías. Mi Sitio Demo SpA no es responsable de daños derivados de su uso, interrupciones, errores técnicos o acceso no autorizado.</p><h2>5. Ley Aplicable</h2><p>Este aviso se rige por las leyes de la República de Chile. Cualquier controversia será resuelta por los juzgados competentes de Santiago.</p>'],
            'en' => ['content' => '<h2>1. Identifying Information</h2><p>In compliance with USA regulations, the following information is provided:</p><ul><li><strong>Company Name:</strong> My Demo Site LLC</li><li><strong>Street Address:</strong> 123 Market Street</li><li><strong>City, State, ZIP:</strong> San Francisco, California 94105</li><li><strong>Phone Number:</strong> +1 415 555 0134</li><li><strong>Business Type:</strong> Digital services</li></ul><h2>2. Acceptance of Terms</h2><p>Access to and use of this website implies full acceptance of the terms and conditions established here. If you do not agree, you must refrain from using this site.</p><h2>3. Intellectual Property</h2><p>All site content (texts, images, designs, logos, software) is intellectual property of My Demo Site LLC or its licensors, protected by US copyright law. Reproduction without authorization is prohibited.</p><h2>4. Limitation of Liability</h2><p>The site is provided "as is" without warranties. My Demo Site LLC is not responsible for damages arising from its use, interruptions, technical errors, or unauthorized access.</p><h2>5. Applicable Law</h2><p>This notice is governed by the laws of the United States. Jurisdiction: courts of California.</p>']
        ], $langIds);

        // ── 2. Política de Privacidad Chile ───────────────────────────
        $privacyId = $this->upsertPage('politica-privacidad', 'privacy', [
            'status'             => 'published',
            'published_at'       => $now,
            'sort_order'         => 110,
            'sitemap_priority'   => '0.5',
            'is_in_sitemap'      => 1,
        ]);
        $this->upsertPageTranslation($privacyId, $langIds['es'], [
            'slug'             => 'politica-privacidad',
            'title'            => 'Política de Privacidad',
            'excerpt'          => 'Cómo recopilamos, usamos y protegemos tus datos personales según la Ley 19.628.',
            'meta_title'       => 'Política de Privacidad | Mi Sitio Demo SpA',
            'meta_description' => 'Información sobre protección de datos personales y privacidad.',
            'canonical_url'    => null,
            'robots'           => 'index, follow',
            'schema_data'      => null,
        ]);
        $this->upsertPageTranslation($privacyId, $langIds['en'], [
            'slug'             => 'privacy-policy',
            'title'            => 'Privacy Policy',
            'excerpt'          => 'How we collect, use and protect your personal data in compliance with CCPA.',
            'meta_title'       => 'Privacy Policy | My Demo Site LLC',
            'meta_description' => 'Information about personal data protection and privacy.',
            'canonical_url'    => null,
            'robots'           => 'index, follow',
            'schema_data'      => null,
        ]);

        $this->resetPageBlocks($privacyId);
        $this->upsertBlock($privacyId, $blockIds, 'page_header', 1, [], [
            'es' => ['heading' => 'Política de Privacidad', 'subheading' => 'Tu privacidad es importante. Conoce cómo protegemos tus datos.'],
            'en' => ['heading' => 'Privacy Policy', 'subheading' => 'Your privacy matters to us. Learn how we protect your data.']
        ], $langIds);
        $this->upsertBlock($privacyId, $blockIds, 'anchor_nav', 2, [], [
            'es' => [
                'anchors' => [
                    ['label' => '1. Responsable', 'anchor_id' => 'responsable'],
                    ['label' => '2. Datos Recopilados', 'anchor_id' => 'datos'],
                    ['label' => '3. Finalidad', 'anchor_id' => 'finalidad'],
                    ['label' => '4. Tus Derechos', 'anchor_id' => 'derechos'],
                    ['label' => '5. Seguridad', 'anchor_id' => 'seguridad'],
                ]
            ],
            'en' => [
                'anchors' => [
                    ['label' => '1. Information We Collect', 'anchor_id' => 'collect'],
                    ['label' => '2. How We Use Information', 'anchor_id' => 'use'],
                    ['label' => '3. Your Rights & Choices', 'anchor_id' => 'rights'],
                    ['label' => '4. Security', 'anchor_id' => 'security'],
                    ['label' => '5. Contact Us', 'anchor_id' => 'contact'],
                ]
            ]
        ], $langIds);
        $this->upsertBlock($privacyId, $blockIds, 'rich_text', 3, [], [
            'es' => ['content' => '<div id="responsable" class="scroll-mt-20"><h2>1. Responsable del Tratamiento</h2><p>El responsable de tus datos es Mi Sitio Demo SpA, RUT 76.123.456-7, ubicado en Avenida Providencia 1234. Contacto: privacidad@example.com</p></div><div id="datos" class="scroll-mt-20"><h2>2. Datos Personales que Recopilamos</h2><p>Recopilamos datos que proporcionas voluntariamente:</p><ul><li>Nombre completo</li><li>Correo electrónico</li><li>Teléfono (opcional)</li><li>Datos de formularios</li><li>Información de navegación (IP, navegador, páginas visitadas)</li></ul></div><div id="finalidad" class="scroll-mt-20"><h2>3. Para Qué Usamos Tus Datos</h2><ul><li>Responder consultas y solicitudes</li><li>Enviar información solicitada</li><li>Mejorar el sitio web</li><li>Cumplir obligaciones legales</li><li>Comunicaciones sobre cambios en políticas</li></ul></div><div id="derechos" class="scroll-mt-20"><h2>4. Tus Derechos (Ley 19.628)</h2><p>Tienes derecho a:</p><ul><li><strong>Acceso:</strong> Conocer qué datos tenemos sobre ti</li><li><strong>Rectificación:</strong> Corregir datos inexactos</li><li><strong>Cancelación:</strong> Solicitar eliminación de tus datos</li><li><strong>Oposición:</strong> Oponerté al uso de tus datos</li></ul><p>Plazo de respuesta: 15 días hábiles. Contacta a privacidad@example.com</p></div><div id="seguridad" class="scroll-mt-20"><h2>5. Seguridad de Tus Datos</h2><p>Implementamos medidas de seguridad:</p><ul><li>Encriptación SSL/TLS</li><li>Acceso restringido a personal autorizado</li><li>Auditorías de seguridad regulares</li><li>Protección contra acceso no autorizado</li></ul><p><strong>Última actualización:</strong> ' . date('d/m/Y') . '</p></div>'],
            'en' => ['content' => '<div id="collect" class="scroll-mt-20"><h2>1. Information We Collect</h2><p>We collect information you voluntarily provide through contact forms and website interactions:</p><ul><li>Full name</li><li>Email address</li><li>Phone number (optional)</li><li>Form submission data</li><li>Navigation information (IP address, browser type, pages visited)</li></ul></div><div id="use" class="scroll-mt-20"><h2>2. How We Use Your Information</h2><ul><li>Respond to your inquiries and requests</li><li>Send requested information</li><li>Improve the website</li><li>Comply with legal obligations</li><li>Communicate about policy changes</li></ul></div><div id="rights" class="scroll-mt-20"><h2>3. Your Rights & Choices (CCPA)</h2><p>Under the California Consumer Privacy Act, you have the right to:</p><ul><li><strong>Know:</strong> What personal information we collect and how we use it</li><li><strong>Delete:</strong> Request deletion of your personal information</li><li><strong>Opt-Out:</strong> Opt out of the sale or sharing of personal information</li><li><strong>Non-Discrimination:</strong> We will not discriminate against you for exercising your privacy rights</li></ul><p>Response time: 45 days. Contact us at privacy@example.com</p></div><div id="security" class="scroll-mt-20"><h2>4. Security</h2><p>We implement security measures to protect your data:</p><ul><li>SSL/TLS encryption</li><li>Restricted access to authorized personnel only</li><li>Regular security audits</li><li>Protection against unauthorized access</li></ul><p><strong>Last updated:</strong> ' . date('m/d/Y') . '</p></div><div id="contact" class="scroll-mt-20"><h2>5. Contact Us</h2><p>For privacy concerns or to exercise your rights, contact us at privacy@example.com</p></div>']
        ], $langIds);

        // ── 3. Política de Cookies Chile ──────────────────────────────
        $cookiesId = $this->upsertPage('politica-cookies', 'generic', [
            'status'             => 'published',
            'published_at'       => $now,
            'sort_order'         => 120,
            'sitemap_priority'   => '0.3',
            'is_in_sitemap'      => 1,
        ]);
        $this->upsertPageTranslation($cookiesId, $langIds['es'], [
            'slug'             => 'politica-cookies',
            'title'            => 'Política de Cookies',
            'excerpt'          => 'Información sobre cookies y tecnologías similares en nuestro sitio.',
            'meta_title'       => 'Política de Cookies | Mi Sitio Demo SpA',
            'meta_description' => 'Cómo usamos cookies y cómo administrar tus preferencias.',
            'canonical_url'    => null,
            'robots'           => 'index, follow',
            'schema_data'      => null,
        ]);
        $this->upsertPageTranslation($cookiesId, $langIds['en'], [
            'slug'             => 'cookie-policy',
            'title'            => 'Cookie Policy',
            'excerpt'          => 'Information about cookies and similar technologies on our website.',
            'meta_title'       => 'Cookie Policy | My Demo Site LLC',
            'meta_description' => 'How we use cookies and how to manage your preferences.',
            'canonical_url'    => null,
            'robots'           => 'index, follow',
            'schema_data'      => null,
        ]);

        $this->resetPageBlocks($cookiesId);
        $this->upsertBlock($cookiesId, $blockIds, 'page_header', 1, [], [
            'es' => ['heading' => 'Política de Cookies', 'subheading' => 'Transparencia en el uso de tecnologías de seguimiento'],
            'en' => ['heading' => 'Cookie Policy', 'subheading' => 'Transparency in the use of tracking technologies']
        ], $langIds);
        $this->upsertBlock($cookiesId, $blockIds, 'rich_text', 2, [], [
            'es' => ['content' => '<h2>¿Qué son las Cookies?</h2><p>Las cookies son archivos pequeños que se almacenan en tu navegador para recordar información sobre ti y mejorar tu experiencia en el sitio.</p><h2>Cookies que Usamos</h2><table class="w-full border-collapse border border-slate-300 mt-4 text-sm"><thead><tr class="bg-slate-100"><th class="border border-slate-300 p-2">Cookie</th><th class="border border-slate-300 p-2">Tipo</th><th class="border border-slate-300 p-2">Duración</th><th class="border border-slate-300 p-2">Propósito</th></tr></thead><tbody><tr><td class="border border-slate-300 p-2">PHPSESSID</td><td class="border border-slate-300 p-2">Técnica</td><td class="border border-slate-300 p-2">Sesión</td><td class="border border-slate-300 p-2">Mantener sesión del usuario</td></tr><tr><td class="border border-slate-300 p-2">cookie_consent</td><td class="border border-slate-300 p-2">Preferencia</td><td class="border border-slate-300 p-2">1 año</td><td class="border border-slate-300 p-2">Almacenar preferencias de consentimiento</td></tr><tr><td class="border border-slate-300 p-2">_ga</td><td class="border border-slate-300 p-2">Analítica</td><td class="border border-slate-300 p-2">2 años</td><td class="border border-slate-300 p-2">Análisis de uso (Google Analytics)</td></tr></tbody></table><h2>Gestión de Cookies</h2><p>Puedes controlar y eliminar cookies desde las preferencias de tu navegador. Ten en cuenta que desactivarlas puede afectar el funcionamiento del sitio.</p>'],
            'en' => ['content' => '<h2>What Are Cookies?</h2><p>Cookies are small files stored in your browser to remember information about you and improve your experience on the site.</p><h2>Cookies We Use</h2><table class="w-full border-collapse border border-slate-300 mt-4 text-sm"><thead><tr class="bg-slate-100"><th class="border border-slate-300 p-2">Cookie</th><th class="border border-slate-300 p-2">Type</th><th class="border border-slate-300 p-2">Duration</th><th class="border border-slate-300 p-2">Purpose</th></tr></thead><tbody><tr><td class="border border-slate-300 p-2">PHPSESSID</td><td class="border border-slate-300 p-2">Technical</td><td class="border border-slate-300 p-2">Session</td><td class="border border-slate-300 p-2">Maintain user session</td></tr><tr><td class="border border-slate-300 p-2">cookie_consent</td><td class="border border-slate-300 p-2">Preference</td><td class="border border-slate-300 p-2">1 year</td><td class="border border-slate-300 p-2">Store consent preferences</td></tr><tr><td class="border border-slate-300 p-2">_ga</td><td class="border border-slate-300 p-2">Analytics</td><td class="border border-slate-300 p-2">2 years</td><td class="border border-slate-300 p-2">Site usage analysis (Google Analytics)</td></tr></tbody></table><h2>Cookie Management</h2><p>You can control and delete cookies from your browser preferences. Note that disabling them may affect site functionality.</p>']
        ], $langIds);

        // ── 4. Términos de Servicio Chile ─────────────────────────────
        $termsId = $this->upsertPage('terminos-servicio', 'terms', [
            'status'             => 'published',
            'published_at'       => $now,
            'sort_order'         => 130,
            'sitemap_priority'   => '0.5',
            'is_in_sitemap'      => 1,
        ]);
        $this->upsertPageTranslation($termsId, $langIds['es'], [
            'slug'             => 'terminos-servicio',
            'title'            => 'Términos de Servicio',
            'excerpt'          => 'Condiciones generales de uso del sitio web.',
            'meta_title'       => 'Términos de Servicio | Mi Sitio Demo SpA',
            'meta_description' => 'Normas y condiciones de uso aplicables.',
            'canonical_url'    => null,
            'robots'           => 'index, follow',
            'schema_data'      => null,
        ]);
        $this->upsertPageTranslation($termsId, $langIds['en'], [
            'slug'             => 'terms-of-service',
            'title'            => 'Terms of Service',
            'excerpt'          => 'General conditions of use for the website.',
            'meta_title'       => 'Terms of Service | My Demo Site LLC',
            'meta_description' => 'Rules and conditions of use.',
            'canonical_url'    => null,
            'robots'           => 'index, follow',
            'schema_data'      => null,
        ]);

        $this->resetPageBlocks($termsId);
        $this->upsertBlock($termsId, $blockIds, 'page_header', 1, [], [
            'es' => ['heading' => 'Términos de Servicio', 'subheading' => 'Normas y condiciones de uso de nuestro sitio'],
            'en' => ['heading' => 'Terms of Service', 'subheading' => 'Rules and conditions of use of our website']
        ], $langIds);
        $this->upsertBlock($termsId, $blockIds, 'rich_text', 2, [], [
            'es' => ['content' => '<h2>1. Aceptación de Términos</h2><p>Al acceder y usar este sitio, aceptas todas las disposiciones de estos términos. Si no estás de acuerdo, no uses el sitio.</p><h2>2. Uso Permitido</h2><p>El sitio es para uso personal y no comercial. Queda prohibido:</p><ul><li>Reproducir o distribuir contenidos sin permiso</li><li>Intentar acceso no autorizado</li><li>Usar bots o herramientas de scraping</li><li>Enviar contenido ilegal o abusivo</li></ul><h2>3. Limitación de Responsabilidad</h2><p>Mi Sitio Demo SpA no es responsable de:</p><ul><li>Interrupciones o errores técnicos</li><li>Pérdida de datos</li><li>Daños derivados del uso del sitio</li><li>Contenido de terceros o enlaces externos</li></ul><h2>4. Protección de Derechos</h2><p>Nos reservamos el derecho de suspender el acceso a usuarios que violen estos términos sin previo aviso.</p><h2>5. Ley Aplicable</h2><p>Estos términos se rigen por las leyes de Chile. Jurisdicción: Tribunales de Santiago.</p><p><strong>Última actualización:</strong> ' . date('d/m/Y') . '</p>'],
            'en' => ['content' => '<h2>1. Acceptance of Terms</h2><p>By accessing and using this website, you accept all provisions of these terms. If you do not agree, do not use the site.</p><h2>2. Permitted Use</h2><p>This site is for personal, non-commercial use. Prohibited activities include:</p><ul><li>Reproducing or distributing content without permission</li><li>Attempting unauthorized access</li><li>Using bots or scraping tools</li><li>Sending illegal or abusive content</li></ul><h2>3. Limitation of Liability</h2><p>My Demo Site LLC is not responsible for:</p><ul><li>Interruptions or technical errors</li><li>Data loss</li><li>Damages arising from site use</li><li>Third-party content or external links</li></ul><h2>4. Rights Protection</h2><p>We reserve the right to suspend access to users who violate these terms without prior notice.</p><h2>5. Applicable Law</h2><p>These terms are governed by the laws of the United States. Jurisdiction: courts of California.</p><p><strong>Last updated:</strong> ' . date('m/d/Y') . '</p>']
        ], $langIds);

        // ── 5. Derechos de Datos Chile ────────────────────────────────
        $dataRightsId = $this->upsertPage('derechos-datos', 'generic', [
            'status'             => 'published',
            'published_at'       => $now,
            'sort_order'         => 140,
            'sitemap_priority'   => '0.5',
            'is_in_sitemap'      => 1,
        ]);
        $this->upsertPageTranslation($dataRightsId, $langIds['es'], [
            'slug'             => 'derechos-datos',
            'title'            => 'Tus Derechos sobre Datos',
            'excerpt'          => 'Conoce tus derechos ARCO bajo la Ley 19.628 y cómo ejercerlos.',
            'meta_title'       => 'Tus Derechos sobre Datos | Mi Sitio Demo SpA',
            'meta_description' => 'Información sobre derechos de acceso, rectificación, cancelación y oposición.',
            'canonical_url'    => null,
            'robots'           => 'index, follow',
            'schema_data'      => null,
        ]);
        $this->upsertPageTranslation($dataRightsId, $langIds['en'], [
            'slug'             => 'data-rights',
            'title'            => 'Your Data Rights',
            'excerpt'          => 'Learn about your rights under CCPA and how to exercise them.',
            'meta_title'       => 'Your Data Rights | My Demo Site LLC',
            'meta_description' => 'Information about access, deletion, and opt-out rights.',
            'canonical_url'    => null,
            'robots'           => 'index, follow',
            'schema_data'      => null,
        ]);

        $this->resetPageBlocks($dataRightsId);
        $this->upsertBlock($dataRightsId, $blockIds, 'page_header', 1, [], [
            'es' => ['heading' => 'Tus Derechos sobre Datos', 'subheading' => 'Cómo ejercer tus derechos según la Ley 19.628'],
            'en' => ['heading' => 'Your Data Rights', 'subheading' => 'How to exercise your rights under CCPA']
        ], $langIds);
        $this->upsertBlock($dataRightsId, $blockIds, 'anchor_nav', 2, [], [
            'es' => [
                'anchors' => [
                    ['label' => '1. Derecho de Acceso', 'anchor_id' => 'acceso'],
                    ['label' => '2. Derecho de Rectificación', 'anchor_id' => 'rectificacion'],
                    ['label' => '3. Derecho de Cancelación', 'anchor_id' => 'cancelacion'],
                    ['label' => '4. Derecho de Oposición', 'anchor_id' => 'oposicion'],
                    ['label' => '5. Cómo Solicitar', 'anchor_id' => 'solicitar'],
                ]
            ],
            'en' => [
                'anchors' => [
                    ['label' => '1. Right to Know', 'anchor_id' => 'know'],
                    ['label' => '2. Right to Delete', 'anchor_id' => 'delete'],
                    ['label' => '3. Right to Opt-Out', 'anchor_id' => 'optout'],
                    ['label' => '4. Right to Non-Discrimination', 'anchor_id' => 'nondiscrim'],
                    ['label' => '5. How to Request', 'anchor_id' => 'request'],
                ]
            ]
        ], $langIds);
        $this->upsertBlock($dataRightsId, $blockIds, 'rich_text', 3, [], [
            'es' => ['content' => '<div id="acceso" class="scroll-mt-20"><h2>1. Derecho de Acceso (Ley 19.628)</h2><p>Tienes derecho a conocer qué datos personales tenemos almacenados sobre ti, para qué se usan y con quién se comparten.</p><p><strong>Cómo solicitarlo:</strong> Envía un correo a privacidad@example.com con "Solicitud de Acceso" en el asunto, incluyendo tu nombre y RUT.</p><p><strong>Plazo:</strong> Respuesta dentro de 15 días hábiles.</p></div><div id="rectificacion" class="scroll-mt-20"><h2>2. Derecho de Rectificación</h2><p>Si los datos que tenemos sobre ti son inexactos, incompletos o desactualizados, puedes solicitar su corrección.</p><p><strong>Cómo solicitarlo:</strong> Envía un correo a privacidad@example.com con "Solicitud de Rectificación", los datos a corregir y la información correcta.</p><p><strong>Plazo:</strong> Respuesta dentro de 15 días hábiles.</p></div><div id="cancelacion" class="scroll-mt-20"><h2>3. Derecho de Cancelación</h2><p>Puedes solicitar la eliminación de tus datos personales cuando ya no sean necesarios para la finalidad por la cual fueron recopilados.</p><p><strong>Nota:</strong> Podemos retener datos si existe obligación legal o necesidad contractual.</p><p><strong>Cómo solicitarlo:</strong> Envía un correo a privacidad@example.com con "Solicitud de Cancelación" y tu RUT.</p></div><div id="oposicion" class="scroll-mt-20"><h2>4. Derecho de Oposición</h2><p>Puedes oponerte al uso de tus datos para marketing, perfilado o procesamiento automatizado.</p><p><strong>Cómo solicitarlo:</strong> Envía un correo a privacidad@example.com con "Solicitud de Oposición" especificando qué usos rechazas.</p></div><div id="solicitar" class="scroll-mt-20"><h2>5. Cómo Solicitar Tus Derechos</h2><p><strong>Datos Requeridos:</strong></p><ul><li>Nombre completo</li><li>RUT o documento de identidad</li><li>Correo electrónico de contacto</li><li>Descripción clara de tu solicitud</li></ul><p><strong>Contacto:</strong></p><p>Email: privacidad@example.com<br>Dirección: Avenida Providencia 1234<br>Teléfono: +56 2 2345 6789</p><p><strong>Verificación:</strong> Podemos solicitar documentos que acrediten tu identidad antes de procesar tu solicitud.</p></div>'],
            'en' => ['content' => '<div id="know" class="scroll-mt-20"><h2>1. Right to Know (CCPA)</h2><p>You have the right to know what personal information we collect, how we use it, and who we share it with.</p><p><strong>How to Request:</strong> Email privacy@example.com with "Data Access Request" in the subject line, including your name.</p><p><strong>Timeline:</strong> We will respond within 45 days.</p></div><div id="delete" class="scroll-mt-20"><h2>2. Right to Delete</h2><p>You can request deletion of your personal information that we have collected from you.</p><p><strong>Note:</strong> We may retain information if required by law or necessary for contractual purposes.</p><p><strong>How to Request:</strong> Email privacy@example.com with "Data Deletion Request" and your full name.</p></div><div id="optout" class="scroll-mt-20"><h2>3. Right to Opt-Out</h2><p>You can opt out of the "sale" or "sharing" of your personal information and opt out of automated decision-making.</p><p><strong>How to Request:</strong> Email privacy@example.com with "Opt-Out Request" specifying which activities you want to opt out of.</p></div><div id="nondiscrim" class="scroll-mt-20"><h2>4. Right to Non-Discrimination</h2><p>We will not discriminate against you for exercising your privacy rights. This means we will not:</p><ul><li>Deny you services or goods</li><li>Charge different prices or rates</li><li>Provide lower quality service</li><li>Suggest you will receive different treatment</li></ul></div><div id="request" class="scroll-mt-20"><h2>5. How to Submit a Request</h2><p><strong>Required Information:</strong></p><ul><li>Full name</li><li>Email address</li><li>Clear description of your request</li><li>Proof of identity (if required)</li></ul><p><strong>Contact Information:</strong></p><p>Email: privacy@example.com<br>Mailing Address: 123 Market Street, San Francisco, California 94105<br>Phone: +1 415 555 0134</p><p><strong>Verification:</strong> We may request additional information to verify your identity before processing your request.</p></div>']
        ], $langIds);

        // ── 6. Transparencia Chile ────────────────────────────────────
        $transparencyId = $this->upsertPage('transparencia', 'generic', [
            'status'             => 'published',
            'published_at'       => $now,
            'sort_order'         => 150,
            'sitemap_priority'   => '0.3',
            'is_in_sitemap'      => 1,
        ]);
        $this->upsertPageTranslation($transparencyId, $langIds['es'], [
            'slug'             => 'transparencia',
            'title'            => 'Transparencia',
            'excerpt'          => 'Información sobre la operación, gobierno y políticas de nuestra empresa.',
            'meta_title'       => 'Transparencia | Mi Sitio Demo SpA',
            'meta_description' => 'Datos sobre estructura corporativa y cumplimiento normativo.',
            'canonical_url'    => null,
            'robots'           => 'index, follow',
            'schema_data'      => null,
        ]);
        $this->upsertPageTranslation($transparencyId, $langIds['en'], [
            'slug'             => 'transparency',
            'title'            => 'Transparency',
            'excerpt'          => 'Information about our company operations, governance and policies.',
            'meta_title'       => 'Transparency | My Demo Site LLC',
            'meta_description' => 'Data about corporate structure and compliance.',
            'canonical_url'    => null,
            'robots'           => 'index, follow',
            'schema_data'      => null,
        ]);

        $this->resetPageBlocks($transparencyId);
        $this->upsertBlock($transparencyId, $blockIds, 'page_header', 1, [], [
            'es' => ['heading' => 'Transparencia Corporativa', 'subheading' => 'Comprometidos con la operación transparente y ética'],
            'en' => ['heading' => 'Corporate Transparency', 'subheading' => 'Committed to transparent and ethical operations']
        ], $langIds);
        $this->upsertBlock($transparencyId, $blockIds, 'rich_text', 2, [], [
            'es' => ['content' => '<h2>1. Estructura Corporativa</h2><p><strong>Razón Social:</strong> Mi Sitio Demo SpA<br><strong>RUT:</strong> 76.123.456-7<br><strong>Giro:</strong> Servicios digitales<br><strong>Domicilio Legal:</strong> Avenida Providencia 1234, Región Metropolitana</p><h2>2. Gobierno Corporativo</h2><p>Nuestra empresa se rige por principios de buen gobierno corporativo:</p><ul><li>Transparencia en la toma de decisiones</li><li>Responsabilidad ante accionistas y stakeholders</li><li>Cumplimiento de normativa aplicable</li><li>Auditorías internas y externas regulares</li><li>Código de ética y conducta para empleados</li></ul><h2>3. Cumplimiento Normativo</h2><p>Operamos en conformidad con:</p><ul><li>Ley 19.628 sobre Protección de Datos</li><li>Ley 19.496 sobre Protección del Consumidor</li><li>Leyes tributarias y laborales aplicables</li><li>Estándares internacionales de seguridad de datos</li></ul><h2>4. Políticas Internas</h2><p>Contamos con políticas documentadas sobre:</p><ul><li>Gestión de conflictos de interés</li><li>Prevención de corrupción y soborno</li><li>Seguridad de la información</li><li>Gestión ambiental y social</li><li>Desarrollo y bienestar de empleados</li></ul><h2>5. Contacto para Consultas</h2><p>Para preguntas sobre transparencia o cumplimiento normativo, contacta a:</p><p>Email: contacto@example.com<br>Teléfono: +56 2 2345 6789</p>'],
            'en' => ['content' => '<h2>1. Corporate Structure</h2><p><strong>Company Name:</strong> My Demo Site LLC<br><strong>Industry:</strong> Digital services<br><strong>Headquarters:</strong> 123 Market Street, San Francisco, California 94105</p><h2>2. Corporate Governance</h2><p>Our company operates under principles of good corporate governance:</p><ul><li>Transparency in decision-making</li><li>Accountability to shareholders and stakeholders</li><li>Compliance with applicable regulations</li><li>Regular internal and external audits</li><li>Code of ethics and conduct for employees</li></ul><h2>3. Regulatory Compliance</h2><p>We operate in compliance with:</p><ul><li>CCPA (California Consumer Privacy Act)</li><li>CAN-SPAM Act and Email Regulations</li><li>FTC Standards for Privacy and Security</li><li>International data security standards</li></ul><h2>4. Internal Policies</h2><p>We maintain documented policies on:</p><ul><li>Conflict of interest management</li><li>Anti-corruption and bribery prevention</li><li>Information security</li><li>Environmental and social responsibility</li><li>Employee development and welfare</li></ul><h2>5. Inquiry Contact</h2><p>For questions about transparency or compliance, contact:</p><p>Email: contact@example.com<br>Phone: +1 415 555 0134</p>']
        ], $langIds);

        // ── 7. Accesibilidad Chile ────────────────────────────────────
        $accessibilityId = $this->upsertPage('accesibilidad', 'generic', [
            'status'             => 'published',
            'published_at'       => $now,
            'sort_order'         => 160,
            'sitemap_priority'   => '0.3',
            'is_in_sitemap'      => 1,
        ]);
        $this->upsertPageTranslation($accessibilityId, $langIds['es'], [
            'slug'             => 'accesibilidad',
            'title'            => 'Declaración de Accesibilidad',
            'excerpt'          => 'Nuestro compromiso con la accesibilidad web (WCAG 2.1 Level AA).',
            'meta_title'       => 'Accesibilidad Web | Mi Sitio Demo SpA',
            'meta_description' => 'Información sobre características de accesibilidad del sitio.',
            'canonical_url'    => null,
            'robots'           => 'index, follow',
            'schema_data'      => null,
        ]);
        $this->upsertPageTranslation($accessibilityId, $langIds['en'], [
            'slug'             => 'accessibility',
            'title'            => 'Accessibility Statement',
            'excerpt'          => 'Our commitment to web accessibility (WCAG 2.1 Level AA).',
            'meta_title'       => 'Web Accessibility | My Demo Site LLC',
            'meta_description' => 'Information about website accessibility features.',
            'canonical_url'    => null,
            'robots'           => 'index, follow',
            'schema_data'      => null,
        ]);

        $this->resetPageBlocks($accessibilityId);
        $this->upsertBlock($accessibilityId, $blockIds, 'page_header', 1, [], [
            'es' => ['heading' => 'Accesibilidad Web', 'subheading' => 'Comprometidos a ser accesibles para todos'],
            'en' => ['heading' => 'Web Accessibility', 'subheading' => 'Committed to accessibility for everyone']
        ], $langIds);
        $this->upsertBlock($accessibilityId, $blockIds, 'anchor_nav', 2, [], [
            'es' => [
                'anchors' => [
                    ['label' => '1. Estándar WCAG', 'anchor_id' => 'wcag'],
                    ['label' => '2. Características Accesibles', 'anchor_id' => 'caracteristicas'],
                    ['label' => '3. Navegación por Teclado', 'anchor_id' => 'teclado'],
                    ['label' => '4. Lectores de Pantalla', 'anchor_id' => 'pantalla'],
                    ['label' => '5. Reportar Problemas', 'anchor_id' => 'reportar'],
                ]
            ],
            'en' => [
                'anchors' => [
                    ['label' => '1. WCAG Standard', 'anchor_id' => 'wcag'],
                    ['label' => '2. Accessible Features', 'anchor_id' => 'features'],
                    ['label' => '3. Keyboard Navigation', 'anchor_id' => 'keyboard'],
                    ['label' => '4. Screen Readers', 'anchor_id' => 'readers'],
                    ['label' => '5. Report Issues', 'anchor_id' => 'report'],
                ]
            ]
        ], $langIds);
        $this->upsertBlock($accessibilityId, $blockIds, 'rich_text', 3, [], [
            'es' => ['content' => '<div id="wcag" class="scroll-mt-20"><h2>1. Estándar WCAG 2.1 Level AA</h2><p>Este sitio web se ha diseñado para cumplir con las Directrices de Accesibilidad para el Contenido Web (WCAG) 2.1 nivel AA. Esto significa que:</p><ul><li>El contenido es perceptible para todos los usuarios</li><li>La interfaz es operable sin ratón</li><li>La información es clara y comprensible</li><li>El sitio funciona con tecnologías de asistencia</li></ul></div><div id="caracteristicas" class="scroll-mt-20"><h2>2. Características Accesibles Implementadas</h2><ul><li><strong>Textos alternativos:</strong> Todas las imágenes tienen descripciones alternativas</li><li><strong>Contraste:</strong> Ratio mínimo 4.5:1 entre texto y fondo</li><li><strong>Tamaño de fuente:</strong> Ajustable mediante navegador</li><li><strong>Estructura semántica:</strong> Uso correcto de encabezados y listas</li><li><strong>Formularios etiquetados:</strong> Campos de formulario claramente asociados</li><li><strong>Navegación consistente:</strong> Menús y estructura predecible</li><li><strong>Links descriptivos:</strong> Textos de enlace claros y significativos</li></ul></div><div id="teclado" class="scroll-mt-20"><h2>3. Navegación por Teclado</h2><p>El sitio es completamente navegable usando solo el teclado:</p><ul><li>Usa <code>Tab</code> para mover entre elementos interactivos</li><li>Usa <code>Shift + Tab</code> para ir hacia atrás</li><li>Usa <code>Enter</code> para activar botones y links</li><li>Usa <code>Esc</code> para cerrar menús o diálogos</li></ul><p>El orden de tabulación sigue la estructura lógica de la página.</p></div><div id="pantalla" class="scroll-mt-20"><h2>4. Compatibilidad con Lectores de Pantalla</h2><p>Este sitio es compatible con lectores de pantalla populares:</p><ul><li><strong>NVDA</strong> (gratuito, Windows): <a href="https://www.nvaccess.org/" target="_blank">www.nvaccess.org</a></li><li><strong>JAWS</strong> (Windows): <a href="https://www.freedomscientific.com/products/software/jaws/" target="_blank">www.freedomscientific.com</a></li><li><strong>VoiceOver</strong> (Mac/iOS): Incluido en el sistema</li><li><strong>TalkBack</strong> (Android): Incluido en el sistema</li></ul><p>Las tecnologías de asistencia pueden encontrar y acceder a todo el contenido importante del sitio.</p></div><div id="reportar" class="scroll-mt-20"><h2>5. Reportar Problemas de Accesibilidad</h2><p>Si encuentras barreras de accesibilidad en nuestro sitio, por favor contacta:</p><p><strong>Email:</strong> contacto@example.com<br><strong>Teléfono:</strong> +56 2 2345 6789<br><strong>Asunto del Email:</strong> "Problema de Accesibilidad"</p><p>Por favor incluye:</p><ul><li>Descripción del problema</li><li>URL de la página afectada</li><li>Navegador y lector de pantalla que usas (si aplica)</li><li>Tu información de contacto</li></ul><p>Nos esforzamos por responder a todos los reportes de accesibilidad en 5 días hábiles.</p></div>'],
            'en' => ['content' => '<div id="wcag" class="scroll-mt-20"><h2>1. WCAG 2.1 Level AA Standard</h2><p>This website has been designed to comply with the Web Content Accessibility Guidelines (WCAG) 2.1 Level AA. This means:</p><ul><li>Content is perceivable to all users</li><li>Interface is operable without a mouse</li><li>Information is clear and understandable</li><li>Site works with assistive technologies</li></ul></div><div id="features" class="scroll-mt-20"><h2>2. Accessible Features Implemented</h2><ul><li><strong>Alt text:</strong> All images have descriptive alternative text</li><li><strong>Contrast:</strong> Minimum 4.5:1 ratio between text and background</li><li><strong>Font size:</strong> Adjustable via browser settings</li><li><strong>Semantic structure:</strong> Correct use of headings and lists</li><li><strong>Labeled forms:</strong> Form fields clearly associated with labels</li><li><strong>Consistent navigation:</strong> Predictable menus and structure</li><li><strong>Descriptive links:</strong> Clear and meaningful link text</li></ul></div><div id="keyboard" class="scroll-mt-20"><h2>3. Keyboard Navigation</h2><p>The site is fully navigable using keyboard only:</p><ul><li>Use <code>Tab</code> to move between interactive elements</li><li>Use <code>Shift + Tab</code> to move backward</li><li>Use <code>Enter</code> to activate buttons and links</li><li>Use <code>Esc</code> to close menus or dialogs</li></ul><p>Tab order follows the logical structure of the page.</p></div><div id="readers" class="scroll-mt-20"><h2>4. Screen Reader Compatibility</h2><p>This site is compatible with popular screen readers:</p><ul><li><strong>NVDA</strong> (free, Windows): <a href="https://www.nvaccess.org/" target="_blank">www.nvaccess.org</a></li><li><strong>JAWS</strong> (Windows): <a href="https://www.freedomscientific.com/products/software/jaws/" target="_blank">www.freedomscientific.com</a></li><li><strong>VoiceOver</strong> (Mac/iOS): Built into system</li><li><strong>TalkBack</strong> (Android): Built into system</li></ul><p>Assistive technologies can find and access all important content on the site.</p></div><div id="report" class="scroll-mt-20"><h2>5. Report Accessibility Issues</h2><p>If you encounter accessibility barriers on our site, please contact:</p><p><strong>Email:</strong> contact@example.com<br><strong>Phone:</strong> +1 415 555 0134<br><strong>Email Subject:</strong> "Accessibility Issue"</p><p>Please include:</p><ul><li>Description of the issue</li><li>URL of the affected page</li><li>Browser and screen reader you are using (if applicable)</li><li>Your contact information</li></ul><p>We aim to respond to all accessibility reports within 5 business days.</p></div>']
        ], $langIds);

        echo "SiteLegalPagesSeederChile: all 7 legal pages seeded successfully (aviso-legal, politica-privacidad, politica-cookies, derechos-datos, transparencia, accesibilidad, terminos-servicio).\n";
    }

    // Helper methods
    private function upsertPage(string $defaultSlug, string $pageType, array $pageData): int
    {
        if (in_array($pageType, ['privacy', 'terms'], true)) {
            $existingSingleton = $this->db->table('cms_pages')
                ->where('page_type', $pageType)
                ->get()
                ->getRowArray();

            if ($existingSingleton !== null) {
                $pageId = (int) $existingSingleton['id'];
                $this->db->table('cms_pages')
                    ->where('id', $pageId)
                    ->update(array_merge($pageData, ['page_type' => $pageType]));
                return $pageId;
            }
        }

        $existing = $this->db->table('cms_page_translations')
            ->where('slug', $defaultSlug)
            ->get()
            ->getRowArray();

        if ($existing !== null) {
            $pageId = (int) $existing['page_id'];
            $this->db->table('cms_pages')
                ->where('id', $pageId)
                ->update(array_merge($pageData, ['page_type' => $pageType]));
            return $pageId;
        }

        $payload = array_merge($pageData, ['page_type' => $pageType]);

        try {
            $this->db->table('cms_pages')->insert($payload);
            return (int) $this->db->insertID();
        } catch (DatabaseException $exception) {
            $fallback = $this->db->table('cms_pages')
                ->where('page_type', $pageType)
                ->get()
                ->getRowArray();

            if ($fallback !== null) {
                $pageId = (int) $fallback['id'];
                $this->db->table('cms_pages')
                    ->where('id', $pageId)
                    ->update($payload);
                return $pageId;
            }

            throw $exception;
        }
    }

    private function upsertPageTranslation(int $pageId, int $languageId, array $translationData): void
    {
        $slug = (string) ($translationData['slug'] ?? '');
        $conflict = $this->db->table('cms_page_translations')
            ->where('language_id', $languageId)
            ->where('slug', $slug)
            ->get()
            ->getRowArray();

        if ($conflict !== null && (int) $conflict['page_id'] !== $pageId) {
            return;
        }

        $this->upsertRecord('cms_page_translations', [
            'page_id'     => $pageId,
            'language_id' => $languageId,
        ], $translationData);
    }

    private function upsertBlock(
        int    $pageId,
        array  $blockIds,
        string $blockKey,
        int    $sortOrder,
        array  $config,
        array  $translations,
        array  $langIds
    ): int {
        $blockId = $blockIds[$blockKey] ?? null;
        if ($blockId === null) {
            return 0;
        }

        $instanceId = $this->upsertRecord('cms_block_instances', [
            'block_id'           => $blockId,
            'owner_type'         => 'page',
            'owner_id'           => $pageId,
            'parent_instance_id' => null,
            'sort_order'         => $sortOrder,
        ], [
            'column_index'       => null,
            'is_active'          => 1,
            'block_config'       => json_encode($config),
        ]);

        foreach ($translations as $langCode => $data) {
            $langId = $langIds[$langCode] ?? null;
            if ($langId === null || !is_array($data)) {
                continue;
            }
            $this->upsertRecord('cms_block_instance_translations', [
                'instance_id' => $instanceId,
                'language_id' => $langId,
            ], [
                'block_data'   => json_encode($data),
                'is_published' => 1,
            ]);
        }

        return $instanceId;
    }

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
}
