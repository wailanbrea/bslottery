-keepattributes *Annotation*
-keepclassmembers class ** {
    @com.squareup.moshi.FromJson *;
    @com.squareup.moshi.ToJson *;
}
-keep class dev.bsolutions.bsloteria.data.remote.dto.** { *; }
-keep class dev.bsolutions.bsloteria.data.local.entity.** { *; }
-dontwarn okhttp3.**
-dontwarn retrofit2.**
