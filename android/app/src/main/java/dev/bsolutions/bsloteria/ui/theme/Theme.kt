package dev.bsolutions.bsloteria.ui.theme

import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.ui.graphics.Color

private val Primary          = Color(0xFF1565C0)
private val PrimaryContainer = Color(0xFFBBDEFB)
private val OnPrimary        = Color.White
private val Secondary        = Color(0xFF2E7D32)
private val OnSecondary      = Color.White
private val Background       = Color(0xFFF5F5F5)
private val Surface          = Color.White
private val OnBackground     = Color(0xFF1A1A1A)
private val OnSurface        = Color(0xFF1A1A1A)
private val OnSurfaceVariant = Color(0xFF5C5C5C)
private val Error            = Color(0xFFB71C1C)
private val OnError          = Color.White

private val LightColors = lightColorScheme(
    primary              = Primary,
    onPrimary            = OnPrimary,
    primaryContainer     = PrimaryContainer,
    onPrimaryContainer   = Color(0xFF002171),
    secondary            = Secondary,
    onSecondary          = OnSecondary,
    secondaryContainer   = Color(0xFFC8E6C9),
    onSecondaryContainer = Color(0xFF003300),
    tertiary             = Color(0xFFE65100),
    onTertiary           = Color.White,
    error                = Error,
    onError              = OnError,
    errorContainer       = Color(0xFFFFCDD2),
    onErrorContainer     = Color(0xFF7F0000),
    background           = Background,
    onBackground         = OnBackground,
    surface              = Surface,
    onSurface            = OnSurface,
    onSurfaceVariant     = OnSurfaceVariant,
    outline              = Color(0xFFBDBDBD),
    surfaceVariant       = Color(0xFFEEEEEE),
)

@Composable
fun BSLoteriaTheme(content: @Composable () -> Unit) {
    // Siempre tema claro — app POS para cajeros, modo oscuro no aplica
    MaterialTheme(
        colorScheme = LightColors,
        content     = content
    )
}
